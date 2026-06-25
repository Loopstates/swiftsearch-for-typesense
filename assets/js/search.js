/* Version: 1.2.6 */
(function () {
    'use strict';

    if (typeof swiftSearchVars === 'undefined') {
        return;
    }

    const config = swiftSearchVars;
    const wrapper = document.getElementById('swift-search-wrapper');
    const input = document.getElementById('ss-search-input');
    const resultsContainer = document.querySelector('.ss-results-container');
    const hitsContainer = document.getElementById('ss-hits');
    const facetsContainer = document.getElementById('ss-facets'); // New container
    const loader = document.querySelector('.ss-loader');

    if (!input || !wrapper) return;

    // Read Data Attributes
    const limit = parseInt(wrapper.dataset.limit) || 10;
    const showThumb = wrapper.dataset.thumb !== 'false';
    const showPrice = wrapper.dataset.price !== 'false';
    const showExcerpt = wrapper.dataset.excerpt === 'true';
    const layout = wrapper.dataset.layout || 'overlay';
    const browseMode = wrapper.dataset.browse === 'true' || layout === 'catalog';

    let currentPage = 1;

    // Experience Config
    const useTypo = config.experience && config.experience.typo_tolerance !== false;
    const enableSort = config.experience && config.experience.sort_enabled === true;
    let currentSort = 'relevance'; // Placeholder for future sort UI

    const rawInstant = wrapper.dataset.instant;
    const rawScope = wrapper.dataset.scope;

    const globalInstant = config.experience && typeof config.experience.instant_search !== 'undefined' ? config.experience.instant_search : true;
    const globalScopeTerms = config.experience && config.experience.search_scope ? config.experience.search_scope.terms : (config.indexed_taxonomies && config.indexed_taxonomies.length > 0);
    const globalScopeUsers = config.experience && config.experience.search_scope ? config.experience.search_scope.users : config.indexed_users;

    // Resolve Instant Search
    let instantSearch = globalInstant;
    if (rawInstant && rawInstant !== 'default') {
        instantSearch = rawInstant === 'true';
    }

    // Resolve Scope
    let scopeTerms = globalScopeTerms;
    let scopeUsers = globalScopeUsers;
    if (rawScope && rawScope !== 'default') {
        const scopes = rawScope.split(',');
        scopeTerms = scopes.includes('terms');
        scopeUsers = scopes.includes('users');
    }

    // In catalog layout, restrict search scope to posts/products only
    if (layout === 'catalog') {
        scopeTerms = false;
        scopeUsers = false;
    }

    // Facet State
    const activeFilters = {}; // { fieldName: ['val1', 'val2'] }

    // Helpers
    function getTypesenseField(item) {
        // 1. Explicit Target Mapping (New System)
        if (item.target) return item.target;

        // 2. Legacy Fallback
        if (item.type === 'taxonomy') {
            if (item.source === 'category') return 'category';
            if (item.source === 'post_tag') return 'tag';
            return 'tax_' + item.source;
        }

        // WooCommerce Special Mappings (Legacy)
        if (item.source === '_sku') return 'sku';
        if (item.source === '_stock_status') return 'in_stock';
        if (item.source === '_price') return 'price';

        return item.source;
    }

    // Build Facet List (Fields to request)
    // Build Facet List (Fields to request)
    let storedFacets = config.facets_config || [];
    if (!Array.isArray(storedFacets)) {
        storedFacets = Object.values(storedFacets);
    }
    const activeFacetsConfig = storedFacets.filter(f => {
        // 1. Basic Enabled Check
        return f && (f.enabled === true || f.enabled === 'true');
    });
    const facetFields = activeFacetsConfig.map(f => getTypesenseField(f)).filter(f => f).join(',');

    let debounceTimer;
    let logTimer;

    function handleInput(e) {
        clearTimeout(debounceTimer);
        const query = input.value.trim();
        currentPage = 1;

        if (query.length === 0) {
            if (browseMode) {
                loader.style.display = 'block';
                debounceTimer = setTimeout(function () {
                    performSearch('*');
                }, 200);
            } else {
                resultsContainer.style.display = 'none';
            }
            return;
        }

        loader.style.display = 'block';

        debounceTimer = setTimeout(function () {
            performSearch(query);
        }, 200);
    }

    if (instantSearch) {
        input.addEventListener('input', handleInput);
    } else {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleInput(e);
            }
        });
    }

    function buildFilterString() {
        const parts = [];
        for (const fieldName in activeFilters) {
            const values = activeFilters[fieldName];
            if (values.length > 0) {
                // Find config for this field to determine type
                const facetConf = activeFacetsConfig.find(f => getTypesenseField(f) === fieldName);
                const dataType = facetConf && facetConf.data_type ? facetConf.data_type : 'string';

                // Type-Safe Quoting: Only wrap strings/string arrays in backticks
                let safeValues;
                const isNumeric = ['int32', 'int64', 'float', 'bool'].includes(dataType) || 
                                  ['int32[]', 'int64[]', 'float[]'].includes(dataType);
                if (isNumeric) {
                    safeValues = values.join(',');
                } else {
                    safeValues = values.map(v => '`' + v + '`').join(',');
                }
                
                parts.push(`${fieldName}:=[${safeValues}]`);
            }
        }
        return parts.join(' && ');
    }

    function performSearch(query) {
        if (!query) {
            if (browseMode) {
                query = '*';
            } else {
                resultsContainer.style.display = 'none';
                return;
            }
        }

        let sortParam = '';
        if (currentSort !== 'relevance') {
            sortParam = `&sort_by=${currentSort}`; // Syntax might depend on library, but for multi_search it is in body
        }

        const numTypos = useTypo ? 2 : 0;
        const baseFilter = buildFilterString();

        // Resolve Post Type Filter (Strict)
        let ptFilter = '';
        let activePTs = [];
        if (wrapper.dataset.postTypes) {
            activePTs = wrapper.dataset.postTypes.split(',').map(s => s.trim()).filter(s => s);
        } else if (config.experience && config.experience.post_types && Array.isArray(config.experience.post_types) && config.experience.post_types.length > 0) {
            activePTs = config.experience.post_types;
        } else if (config.indexed_post_types && Array.isArray(config.indexed_post_types)) {
            // Fallback to indexed types if experience scope is not set
            activePTs = config.indexed_post_types;
        }

        if (activePTs.length > 0) {
            ptFilter = `post_type:=[${activePTs.map(v => '`' + v + '`').join(',')}]`;
        }

        let finalFilter = baseFilter;
        if (ptFilter) {
            finalFilter = baseFilter ? `(${baseFilter}) && ${ptFilter}` : ptFilter;
        }

        // Build Multi-Search Requests
        const searches = [];

        // 1. Posts (Always)
        const postsParams = {
            collection: 'posts',
            q: query,
            query_by: 'post_title,post_content',
            per_page: limit,
            page: currentPage,
            num_typos: numTypos,
            prefix: 'true,true', // Standard and Prefix support (e.g. "hoo" -> "Hoodie")
            sort_by: currentSort === 'relevance' ? '_text_match:desc' : currentSort
        };

        if (facetFields) {
            postsParams.facet_by = facetFields;
        }

        // Relevance Weights (Client-Side)
        if (config.weights && (config.weights.post_title || config.weights.post_content)) {
            const tWeight = config.weights.post_title || 1;
            const cWeight = config.weights.post_content || 1;
            postsParams.query_by_weights = `${tWeight},${cWeight}`;
        }

        // Pinned Items (Client-Side)
        if (config.pinned_items && Array.isArray(config.pinned_items) && config.pinned_items.length > 0) {
            const pinnedIds = config.pinned_items.map((i, idx) => `${i.id}:${idx + 1}`).join(',');
            if (pinnedIds) {
                postsParams.pinned_hits = pinnedIds;
            }
        }

        if (finalFilter) {
            postsParams.filter_by = finalFilter;
        }

        if (config.synonym_sets && config.synonym_sets.length > 0) {
            postsParams.synonym_sets = config.synonym_sets.join(',');
        }

        searches.push(postsParams);

        // 2. Taxonomies (If enabled in Scope)
        if (scopeTerms && config.indexed_taxonomies && config.indexed_taxonomies.length > 0) {
            searches.push({
                collection: 'terms',
                q: query,
                query_by: 'name,taxonomy',
                prefix: 'true,true', // Standard and Prefix support
                per_page: 5,
                num_typos: numTypos
            });
        }

        // 3. Users (If enabled in Scope)
        if (scopeUsers && config.indexed_users) {
            searches.push({
                collection: 'users',
                q: query,
                query_by: 'display_name,user_login',
                prefix: 'true,true', // Standard and Prefix support
                per_page: 5,
                num_typos: numTypos
            });
        }

        const url = `${config.protocol}://${config.host}:${config.port}/multi_search`;

        // Dispatch Custom Event for Before Search
        const beforeSearchEvent = new CustomEvent('swift-search:before-search', {
            detail: {
                query: query,
                searches: searches
            },
            bubbles: true,
            cancelable: true
        });
        wrapper.dispatchEvent(beforeSearchEvent);

        if (beforeSearchEvent.defaultPrevented) {
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'X-TYPESENSE-API-KEY': config.apiKey,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ searches: searches })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Typesense request failed');
                }
                return response.json();
            })
            .then(data => {
                renderHits(data, searches);
            })
            .catch(err => {
                hitsContainer.innerHTML = '<div class="ss-no-results">Search currently unavailable. Please try again later.</div>';
            });
    }

    function renderHits(data, searches) {
        resultsContainer.style.display = 'flex';
        hitsContainer.innerHTML = '';
        if (facetsContainer) {
            facetsContainer.innerHTML = '';
            facetsContainer.style.display = 'none';
        }

        const results = data && data.results ? data.results : [];

        // 0. Handle Sidebar Visibility (Conditional)
        const hasFacets = results.some(r => r.facet_counts && r.facet_counts.length > 0);
        if (!hasFacets) {
            resultsContainer.classList.add('ss-no-sidebar');
        } else {
            resultsContainer.classList.remove('ss-no-sidebar');
        }

        if (results.length === 0 || !results.some(r => r.found > 0)) {
            const queryVal = input.value.trim();
            const msg = queryVal ? `No results found for "<strong>${queryVal}</strong>"` : 'No results found.';
            hitsContainer.innerHTML = `
                <div class="ss-no-results">
                    <span class="dashicons dashicons-search"></span>
                    <p>${msg}</p>
                </div>`;
            return;
        }

        // Render Facets (from Posts collection)
        const postsResult = results[0]; // Always first
        if (postsResult && postsResult.facet_counts && activeFacetsConfig.length > 0) {
            renderFacets(postsResult.facet_counts);
        }

        let organicFoundTotal = 0;
        let hasHits = false;

        // Section Containers
        const sections = {
            posts: document.createElement('div'),
            terms: document.createElement('div'),
            users: document.createElement('div')
        };

        // Initialize Section Classes
        Object.keys(sections).forEach(k => {
            sections[k].className = `ss-section ss-section-${k}`;
            sections[k].style.display = 'none';
        });

        results.forEach((result, index) => {
            const collection = searches[index].collection;
            const section = sections[collection];

            if (result.found > 0) {
                // Determine organic matches (ignore hits that only appeared because they were pinned/curated)
                // A hit is 'curated' if it was specifically injected by pinned_hits or an override.
                const curatedInPage = result.hits ? result.hits.filter(h => h.curated).length : 0;
                
                // If found is 2 and we have 2 curated hits, organic matches for this collection is 0.
                const organicFound = Math.max(0, result.found - curatedInPage);
                organicFoundTotal += organicFound;

                if (result.found > 0) {
                    hasHits = true;
                    section.style.display = 'block';
                }

                // Section Header
                let title = 'Items';
                if (collection === 'posts') {
                    // Dynamic Heading Logic
                    let activePTs = [];
                    if (wrapper.dataset.postTypes) {
                        activePTs = wrapper.dataset.postTypes.split(',').map(s => s.trim()).filter(s => s);
                    } else if (config.experience && config.experience.post_types && Array.isArray(config.experience.post_types) && config.experience.post_types.length > 0) {
                        activePTs = config.experience.post_types;
                    } else if (config.indexed_post_types && Array.isArray(config.indexed_post_types)) {
                        activePTs = config.indexed_post_types;
                    }

                    if (activePTs.length === 1) {
                        const pt = activePTs[0];
                        if (pt === 'product') title = 'Products';
                        else if (pt === 'post') title = 'Posts';
                        else if (pt === 'page') title = 'Pages';
                        else title = pt.charAt(0).toUpperCase() + pt.slice(1) + 's';
                    } else if (activePTs.length > 1) {
                        const labels = activePTs.map(pt => {
                            if (pt === 'product') return 'Products';
                            if (pt === 'post') return 'Posts';
                            if (pt === 'page') return 'Pages';
                            return pt.charAt(0).toUpperCase() + pt.slice(1) + 's';
                        });
                        
                        if (labels.length === 2) {
                            title = labels.join(' & ');
                        } else {
                            title = labels.slice(0, -1).join(', ') + ' & ' + labels.slice(-1);
                        }
                    } else {
                        title = 'Results';
                    }
                }
                else if (collection === 'terms') title = 'Categories';
                else if (collection === 'users') title = 'Authors';

                const header = document.createElement('div');
                header.className = 'ss-section-header';
                header.innerHTML = `
                    <h3 class="ss-section-title">${title}</h3>
                    <span class="ss-section-count">${result.found} found</span>
                `;
                section.appendChild(header);

                const grid = document.createElement('div');
                grid.className = 'ss-grid';
                section.appendChild(grid);

                const renderedNames = new Set();
                result.hits.forEach((hit, hitIndex) => {
                    const doc = hit.document;

                    // Deduplicate terms (categories/tags) by name to prevent reduncancy
                    if (collection === 'terms' && doc.name) {
                        if (renderedNames.has(doc.name)) {
                            return;
                        }
                        renderedNames.add(doc.name);
                    }

                    const card = document.createElement('div');
                    card.className = 'ss-card';
                    if (hit.curated) {
                        card.classList.add('ss-card-pinned');
                    }
                    card.style.animationDelay = `${hitIndex * 0.05}s`;

                    let html = '';
                    if (collection === 'posts') {
                        const isProduct = doc.post_type === 'product';
                        card.classList.add(isProduct ? 'ss-card-product' : 'ss-card-post');

                        html = `
                            <a href="${doc.permalink}" class="ss-card-link">
                                ${showThumb ? `
                                <div class="ss-card-image">
                                    ${doc.thumbnail_url ? `<img src="${doc.thumbnail_url}" alt="">` : '<div class="ss-placeholder"></div>'}
                                </div>` : ''}
                                <div class="ss-card-body">
                                    <h4 class="ss-card-title">${highlight(hit, 'post_title')}</h4>
                                    ${(showPrice && isProduct && typeof doc.price !== 'undefined' && doc.price !== null) ? `<div class="ss-card-price">$${doc.price}</div>` : ''}
                                    ${(showExcerpt && doc.post_excerpt) ? `<p class="ss-card-excerpt">${doc.post_excerpt}</p>` : ''}
                                </div>
                            </a>`;
                    } else {
                        // Generic card for Terms/Users with clean labels
                        let cleanTag = '';
                        if (collection === 'terms') {
                            if (doc.taxonomy === 'product_cat' || doc.taxonomy === 'category') {
                                cleanTag = 'Category';
                            } else if (doc.taxonomy === 'post_tag') {
                                cleanTag = 'Tag';
                            } else if (doc.taxonomy) {
                                cleanTag = doc.taxonomy.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
                            }
                        } else if (collection === 'users') {
                            cleanTag = 'Author';
                        }

                        html = `
                            <a href="${doc.url || '#'}" class="ss-card-link ss-card-compact">
                                <div class="ss-card-body">
                                    <h4 class="ss-card-title">${highlight(hit, collection === 'terms' ? 'name' : 'display_name')}</h4>
                                    ${cleanTag ? `<span class="ss-card-tag">${cleanTag}</span>` : ''}
                                </div>
                            </a>`;
                    }
                    card.innerHTML = html;

                    // Dispatch Custom Event for Hit Rendering
                    const hitEvent = new CustomEvent('swift-search:render-hit', {
                        detail: {
                            hit: hit,
                            card: card,
                            collection: collection
                        },
                        bubbles: true
                    });
                    wrapper.dispatchEvent(hitEvent);

                    grid.appendChild(card);
                });
            }
        });

        // Append active sections to container
        Object.values(sections).forEach(s => {
            if (s.style.display !== 'none') {
                hitsContainer.appendChild(s);
            }
        });

        if (!hasHits) {
            hitsContainer.innerHTML = '<div class="ss-no-results">No results found.</div>';
        } else if (layout === 'catalog' && postsResult && postsResult.found > 0) {
            const totalFound = postsResult.found;
            const totalPages = Math.ceil(totalFound / limit);
            if (totalPages > 1) {
                renderPagination(totalPages);
            }
        }
        
        // Log based on ORGANIC results (ignore hits that were only forced by pinning)
        logSearch(input.value.trim(), organicFoundTotal);

        // Dispatch Custom Event for Results Rendered
        const renderedEvent = new CustomEvent('swift-search:results-rendered', {
            detail: {
                data: data,
                totalFound: organicFoundTotal
            },
            bubbles: true
        });
        wrapper.dispatchEvent(renderedEvent);
        
        loader.style.display = 'none';
    }

    function renderPagination(totalPages) {
        const pagination = document.createElement('div');
        pagination.className = 'ss-pagination';

        // Prev Button
        const prevLink = document.createElement('a');
        prevLink.href = '#';
        prevLink.className = 'ss-page-link prev' + (currentPage === 1 ? ' disabled' : '');
        prevLink.innerHTML = '&laquo;';
        if (currentPage > 1) {
            prevLink.onclick = (e) => {
                e.preventDefault();
                currentPage--;
                performSearch(input.value.trim());
                wrapper.scrollIntoView({ behavior: 'smooth' });
            };
        }
        pagination.appendChild(prevLink);

        // Page Numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        // Adjust start page if we are near the end
        let adjustedStart = startPage;
        if (endPage - startPage < 4) {
            adjustedStart = Math.max(1, endPage - 4);
        }

        for (let i = adjustedStart; i <= endPage; i++) {
            const pageLink = document.createElement('a');
            pageLink.href = '#';
            pageLink.className = 'ss-page-link' + (i === currentPage ? ' active' : '');
            pageLink.innerText = i;
            pageLink.onclick = (e) => {
                e.preventDefault();
                currentPage = i;
                performSearch(input.value.trim());
                wrapper.scrollIntoView({ behavior: 'smooth' });
            };
            pagination.appendChild(pageLink);
        }

        // Next Button
        const nextLink = document.createElement('a');
        nextLink.href = '#';
        nextLink.className = 'ss-page-link next' + (currentPage === totalPages ? ' disabled' : '');
        nextLink.innerHTML = '&raquo;';
        if (currentPage < totalPages) {
            nextLink.onclick = (e) => {
                e.preventDefault();
                currentPage++;
                performSearch(input.value.trim());
                wrapper.scrollIntoView({ behavior: 'smooth' });
            };
        }
        pagination.appendChild(nextLink);

        hitsContainer.appendChild(pagination);
    }

    function renderFacets(facetCounts) {
        if (!facetsContainer) return;
        
        let hasVisibleFacets = false;
        facetsContainer.innerHTML = ''; // Reset

        activeFacetsConfig.forEach(conf => {
            const fieldName = getTypesenseField(conf);
            const facetData = facetCounts.find(f => f.field_name === fieldName);

            if (facetData && facetData.counts.length > 0) {
                const group = document.createElement('div');
                group.className = 'ss-facet-group';

                const title = document.createElement('h5');
                title.className = 'ss-facet-title';
                title.innerText = conf.label || conf.source;
                group.appendChild(title);

                const list = document.createElement('ul');
                list.className = 'ss-facet-list';

                facetData.counts.forEach(c => {
                    const li = document.createElement('li');
                    const label = c.value;
                    const count = c.count;
                    const isChecked = activeFilters[fieldName] && activeFilters[fieldName].includes(label);

                    li.innerHTML = `
                        <label>
                            <input type="checkbox" value="${label}" ${isChecked ? 'checked' : ''}>
                            ${label} <span class="ss-facet-count">(${count})</span>
                        </label>
                    `;

                    // Bind Click
                    li.querySelector('input').addEventListener('change', (e) => {
                        toggleFilter(fieldName, label, e.target.checked);
                    });

                    list.appendChild(li);
                });

                group.appendChild(list);
                facetsContainer.appendChild(group);
                hasVisibleFacets = true;
            }
        });

        if (hasVisibleFacets) {
            facetsContainer.style.display = 'block';
            
            // Add Clear All button if filters active
            const hasFilters = Object.values(activeFilters).some(v => v.length > 0);
            if (hasFilters) {
                const clearBtn = document.createElement('button');
                clearBtn.className = 'ss-facet-clear-all';
                clearBtn.innerText = 'Clear All Filters';
                clearBtn.onclick = () => {
                    for (const k in activeFilters) activeFilters[k] = [];
                    currentPage = 1;
                    performSearch(input.value.trim());
                };
                facetsContainer.prepend(clearBtn);
            }
        }
    }

    function toggleFilter(field, value, checked) {
        if (!activeFilters[field]) activeFilters[field] = [];

        if (checked) {
            activeFilters[field].push(value);
        } else {
            activeFilters[field] = activeFilters[field].filter(v => v !== value);
        }

        // Trigger search
        currentPage = 1;
        performSearch(input.value.trim());
    }

    function highlight(hit, field) {
        if (hit.highlights && hit.highlights.some(h => h.field === field)) {
            const h = hit.highlights.find(x => x.field === field);
            return h.snippet;
        }
        return hit.document[field];
    }

    function logSearch(query, hits) {
        clearTimeout(logTimer);
        if (!query || query.length < 2) return;

        // Debounce to avoid spamming while typing
        logTimer = setTimeout(() => {
            const payload = { query: query, hits: hits };



            const headers = {
                'Content-Type': 'application/json'
            };
            if (config.nonce) {
                headers['X-WP-Nonce'] = config.nonce;
            }

            fetch(config.apiUrl + '/log', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(payload)
            }).then(response => {
                if (!response.ok) console.warn('SwiftSearch Log Failed', response.status);
            }).catch(e => { /* Error logged silently */ });
        }, 2000);
    }

    if (browseMode) {
        performSearch('*');
    }

})();
