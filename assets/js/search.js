/* Version: 1.1.0 */
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

    // Facet State
    const activeFilters = {}; // { fieldName: ['val1', 'val2'] }

    // Helpers
    function getTypesenseField(item) {
        if (item.type === 'taxonomy') {
            if (item.source === 'category') return 'category';
            if (item.source === 'post_tag') return 'tag';
            return 'tax_' + item.source;
        } else if (item.type === 'meta') {
            // Find mapped name
            if (config.custom_fields) {
                // custom_fields is { post: [], product: [] }
                // We search all types
                for (const pt in config.custom_fields) {
                    const found = config.custom_fields[pt].find(f => f.key === item.source);
                    if (found) return found.name;
                }
            }
            return item.source; // Fallback
        }

        // WooCommerce Special Mappings
        if (item.source === '_sku') return 'sku';
        if (item.source === '_stock_status') return 'in_stock';
        if (item.source === '_price') return 'price';

        return item.source;
    }

    // Build Facet List (Fields to request)
    // Build Facet List (Fields to request)
    const storedFacets = config.facets_config || [];
    const activeFacetsConfig = storedFacets.filter(f => {
        // 1. Basic Enabled Check
        const isEnabled = f.enabled === true || f.enabled === 'true';
        if (!isEnabled) return false;

        // 2. Schema Validation (Prevent 400 Errors)
        if (f.type === 'taxonomy') {
            // 'category' and 'post_tag' are always in schema. Others must be indexed.
            if (f.source === 'category' || f.source === 'post_tag') return true;
            return config.indexed_taxonomies && config.indexed_taxonomies.includes(f.source);
        }

        // 3. Meta Validation implies it exists in schema if it's standard or mapped
        // We trust the mapping logic in getTypesenseField will return a valid name if mapped
        return true;
    });
    const facetFields = activeFacetsConfig.map(f => getTypesenseField(f)).join(',');

    let debounceTimer;
    let logTimer;

    function handleInput(e) {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        if (query.length === 0) {
            resultsContainer.style.display = 'none';
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
        for (const field in activeFilters) {
            const values = activeFilters[field];
            if (values.length > 0) {
                // Escape values? Typesense handles mostly, but exact match := is safe for encoded strings
                // value string format: [v1, v2]
                // We need to map values to string
                const safeValues = values.map(v => '`' + v + '`').join(',');
                parts.push(`${field}:=[${safeValues}]`);
            }
        }
        return parts.join(' && ');
    }

    function performSearch(query) {
        let sortParam = '';
        if (currentSort !== 'relevance') {
            sortParam = `&sort_by=${currentSort}`; // Syntax might depend on library, but for multi_search it is in body
        }

        const numTypos = useTypo ? 2 : 0;
        const baseFilter = buildFilterString();

        // Resolve Post Type Filter
        let ptFilter = '';
        if (wrapper.dataset.postTypes) {
            const pts = wrapper.dataset.postTypes.split(',').map(s => s.trim()).filter(s => s);
            if (pts.length > 0) {
                ptFilter = `post_type:=[${pts.map(v => '`' + v + '`').join(',')}]`;
            }
        } else if (config.experience && config.experience.post_types && Array.isArray(config.experience.post_types) && config.experience.post_types.length > 0) {
            const pts = config.experience.post_types;
            ptFilter = `post_type:=[${pts.map(v => '`' + v + '`').join(',')}]`;
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
            const pinnedIds = config.pinned_items.map(i => i.id).join(',');
            if (pinnedIds) {
                postsParams.pinned_hits = pinnedIds;
            }
        }

        if (finalFilter) {
            postsParams.filter_by = finalFilter;
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

        // DEBUG: Log the payload
        console.log('Typesense Request:', searches);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-TYPESENSE-API-KEY': config.apiKey,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ searches: searches })
        })
            .then(async response => {
                // Robust Error Catching
                if (!response.ok) {
                    const errData = await response.json().catch(() => ({ message: 'Unknown Error' }));
                    console.error("SwiftSearch: API Error", response.status, errData);

                    // Self-Healing: If 400/404 (likely Bad Facet/Field), Retry without facets
                    if (response.status === 400 || response.status === 404) {
                        console.warn("SwiftSearch: Schema/Facet Error detected. Retrying without facets...");
                        
                        // Deep clone searches and strip facets
                        const rawSearches = JSON.parse(JSON.stringify(searches));
                        rawSearches.forEach(s => delete s.facet_by);

                        const retryResp = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-TYPESENSE-API-KEY': config.apiKey,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ searches: rawSearches })
                        });
                        return retryResp.json();
                    }
                }
                return response.json();
            })
            .then(data => {
                loader.style.display = 'none';
                renderHits(data, searches);
            })
            .catch(err => {
                console.error("SwiftSearch: Request Failed", err);
                loader.style.display = 'none';
                hitsContainer.innerHTML = '<div class="ss-no-results">Search currently unavailable. Please try again later.</div>';
            });
    }

    function renderHits(data, searches) {
        resultsContainer.style.display = 'flex';
        hitsContainer.innerHTML = '';
        if (facetsContainer) facetsContainer.innerHTML = '';

        const results = data && data.results ? data.results : [];

        // 0. Handle Sidebar Visibility (Conditional)
        const hasFacets = results.some(r => r.facet_counts && r.facet_counts.length > 0);
        if (!hasFacets) {
            resultsContainer.classList.add('ss-no-sidebar');
        } else {
            resultsContainer.classList.remove('ss-no-sidebar');
        }

        if (results.length === 0 || !results.some(r => r.found > 0)) {
            hitsContainer.innerHTML = `
                <div class="ss-no-results">
                    <span class="dashicons dashicons-search"></span>
                    <p>No results found for "<strong>${input.value}</strong>"</p>
                </div>`;
            return;
        }

        // Render Facets (from Posts collection)
        const postsResult = results[0]; // Always first
        if (postsResult && postsResult.facet_counts && activeFacetsConfig.length > 0) {
            renderFacets(postsResult.facet_counts);
        }

        let totalFound = 0;
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
                totalFound += result.found;
                hasHits = true;
                section.style.display = 'block';

                // Section Header
                let title = 'Items';
                if (collection === 'posts') title = 'Products & Posts';
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

                result.hits.forEach((hit, hitIndex) => {
                    const doc = hit.document;
                    const card = document.createElement('div');
                    card.className = 'ss-card';
                    card.style.animationDelay = `${hitIndex * 0.05}s`;

                    let html = '';
                    if (collection === 'posts') {
                        const isProduct = doc.post_type === 'product';
                        card.classList.add(isProduct ? 'ss-card-product' : 'ss-card-post');

                        html = `
                            <a href="${doc.permalink}" class="ss-card-link">
                                <div class="ss-card-image">
                                    ${doc.thumbnail_url ? `<img src="${doc.thumbnail_url}" alt="">` : '<div class="ss-placeholder"></div>'}
                                </div>
                                <div class="ss-card-body">
                                    <h4 class="ss-card-title">${highlight(hit, 'post_title')}</h4>
                                    ${isProduct && doc.price ? `<div class="ss-card-price">$${doc.price}</div>` : ''}
                                    ${doc.post_excerpt ? `<p class="ss-card-excerpt">${doc.post_excerpt}</p>` : ''}
                                </div>
                            </a>`;
                    } else {
                        // Generic card for Terms/Users
                        html = `
                            <a href="${doc.url || '#'}" class="ss-card-link ss-card-compact">
                                <div class="ss-card-body">
                                    <h4 class="ss-card-title">${highlight(hit, collection === 'terms' ? 'name' : 'display_name')}</h4>
                                    ${doc.taxonomy ? `<span class="ss-card-tag">${doc.taxonomy}</span>` : ''}
                                </div>
                            </a>`;
                    }
                    card.innerHTML = html;
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
            loader.style.display = 'none';
        } else {
            logSearch(input.value.trim(), totalFound);
        }
    }

    function renderFacets(facetCounts) {
        if (!facetsContainer) return;
        facetsContainer.style.display = 'block';

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
            }
        });
    }

    function toggleFilter(field, value, checked) {
        if (!activeFilters[field]) activeFilters[field] = [];

        if (checked) {
            activeFilters[field].push(value);
        } else {
            activeFilters[field] = activeFilters[field].filter(v => v !== value);
        }

        // Trigger search
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

            // console.log('SwiftSearch Logging:', payload); // Debug

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
            }).catch(e => console.error('Log error', e));
        }, 2000);
    }

})();
