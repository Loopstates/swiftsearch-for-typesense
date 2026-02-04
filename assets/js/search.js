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
        return item.source;
    }

    // Build Facet List (Fields to request)
    const activeFacetsConfig = (config.facets_config || []).filter(f => f.enabled);
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
        const filterString = buildFilterString();

        // Build Multi-Search Requests
        const searches = [];

        // 1. Posts (Always)
        const postsParams = {
            collection: 'posts',
            q: query,
            query_by: 'post_title,post_content',
            per_page: limit,
            num_typos: numTypos,
            sort_by: currentSort === 'relevance' ? '_text_match:desc' : currentSort,
            facet_by: facetFields
        };

        if (filterString) {
            postsParams.filter_by = filterString;
        }

        searches.push(postsParams);

        // 2. Taxonomies (If enabled in Scope)
        if (scopeTerms && config.indexed_taxonomies && config.indexed_taxonomies.length > 0) {
            searches.push({
                collection: 'terms',
                q: query,
                query_by: 'name,taxonomy',
                per_page: Math.ceil(limit / 2),
                num_typos: numTypos
            });
        }

        // 3. Users (If enabled in Scope)
        if (scopeUsers && config.indexed_users) {
            searches.push({
                collection: 'users',
                q: query,
                query_by: 'display_name,user_login',
                per_page: Math.ceil(limit / 2),
                num_typos: numTypos
            });
        }

        const url = `${config.protocol}://${config.host}:${config.port}/multi_search`;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-TYPESENSE-API-KEY': config.apiKey,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ searches: searches })
        })
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';
                renderHits(data, searches);
            })
            .catch(err => {
                console.error(err);
                loader.style.display = 'none';
            });
    }

    function renderHits(data, searches) {
        resultsContainer.style.display = 'flex'; // Use flex to show sidebar
        hitsContainer.innerHTML = '';
        if (facetsContainer) facetsContainer.innerHTML = '';

        const results = data.results || [];
        if (!results.length) return;

        // Render Facets (from Posts collection)
        const postsResult = results[0]; // Always first
        if (postsResult && postsResult.facet_counts && activeFacetsConfig.length > 0) {
            renderFacets(postsResult.facet_counts);
        }

        let totalFound = 0;
        let hasHits = false;

        results.forEach((result, index) => {
            const searchParams = searches[index];
            const collection = searchParams.collection;

            if (result.found > 0) {
                totalFound += result.found;
                hasHits = true;

                // Section Header
                let title = 'Posts';
                if (collection === 'terms') title = 'Categories & Tags';
                else if (collection === 'users') title = 'Authors';

                if (results.length > 1) {
                    const header = document.createElement('h4');
                    header.className = 'ss-section-header';
                    header.innerText = title;
                    hitsContainer.appendChild(header);
                }

                result.hits.forEach(hit => {
                    const doc = hit.document;
                    const el = document.createElement('div');
                    el.className = 'ss-hit';

                    let html = '';
                    if (collection === 'posts') {
                        html = `<a href="${doc.permalink}" class="ss-hit-link">`;
                        if (showThumb && doc.thumbnail_url) {
                            html += `<img src="${doc.thumbnail_url}" alt="" class="ss-hit-thumb">`;
                        }
                        html += `<div class="ss-hit-content">`;
                        html += `<h3 class="ss-hit-title">${highlight(hit, 'post_title')}</h3>`;
                        if (showExcerpt && doc.post_excerpt) {
                            html += `<p class="ss-hit-excerpt">${doc.post_excerpt}</p>`;
                        }
                        if (showPrice && doc.price) {
                            html += `<span class="ss-hit-price">$${doc.price}</span>`;
                        }
                        html += `</div></a>`;
                    } else if (collection === 'terms') {
                        html = `<a href="${doc.url}" class="ss-hit-link ss-hit-term">`;
                        html += `<div class="ss-hit-content">`;
                        html += `<h3 class="ss-hit-title">${highlight(hit, 'name')}</h3>`;
                        html += `<span class="ss-hit-meta">${doc.taxonomy}</span>`;
                        html += `</div></a>`;
                    } else if (collection === 'users') {
                        html = `<a href="${doc.url}" class="ss-hit-link ss-hit-user">`;
                        if (doc.avatar_url) {
                            html += `<img src="${doc.avatar_url}" alt="" class="ss-hit-avatar">`;
                        }
                        html += `<div class="ss-hit-content">`;
                        html += `<h3 class="ss-hit-title">${highlight(hit, 'display_name')}</h3>`;
                        html += `</div></a>`;
                    }
                    el.innerHTML = html;
                    hitsContainer.appendChild(el);
                });
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
        logTimer = setTimeout(() => {
            const payload = { query: query, hits: hits };
            fetch(config.apiUrl + '/log', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).catch(e => console.error('Log error', e));
        }, 2000);
    }

})();
