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
    const loader = document.querySelector('.ss-loader');

    if (!input || !wrapper) return;

    // Read Data Attributes
    const limit = parseInt(wrapper.dataset.limit) || 10;
    const showThumb = wrapper.dataset.thumb !== 'false';
    const showPrice = wrapper.dataset.price !== 'false';
    const showExcerpt = wrapper.dataset.excerpt === 'true';

    // Experience Config
    const useTypo = config.experience && config.experience.typo_tolerance !== false; // Default true if undefined
    const enableSort = config.experience && config.experience.sort_enabled === true;

    // Data Attributes Override & Global Fallback
    const rawInstant = wrapper.dataset.instant; // 'true', 'false', 'default'
    const rawScope = wrapper.dataset.scope; // 'posts,terms', 'default'

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
                e.preventDefault(); // Prevent form submit if inside form
                handleInput(e);
            }
        });
    }

    function performSearch(query) {
        let sortParam = '';
        if (currentSort !== 'relevance') {
            sortParam = `&sort_by=${currentSort}`;
        }

        const numTypos = useTypo ? 2 : 0;

        // Build Multi-Search Requests
        const searches = [];

        // 1. Posts (Always)
        searches.push({
            collection: 'posts',
            q: query,
            query_by: 'post_title,post_content',
            per_page: limit,
            num_typos: numTypos,
            sort_by: currentSort === 'relevance' ? '_text_match:desc' : currentSort // Default sort if relevance
        });

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
        const commonParams = { 'x-typesense-api-key': config.apiKey };

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
        resultsContainer.style.display = 'flex';
        hitsContainer.innerHTML = '';

        // data.results matches searches array order
        const results = data.results || [];

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

                // Only show header if we have mixed results
                if (results.length > 1) {
                    const header = document.createElement('h4');
                    header.className = 'ss-section-header';
                    header.style.margin = '10px 0 5px 0';
                    header.style.fontSize = '12px';
                    header.style.textTransform = 'uppercase';
                    header.style.color = '#888';
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
                            html += `<img src="${doc.avatar_url}" alt="" class="ss-hit-avatar" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">`;
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
            logSearch(input.value.trim(), 0);
            return;
        }

        logSearch(input.value.trim(), totalFound);
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

        // Don't log empty
        if (!query || query.length < 2) return;

        logTimer = setTimeout(() => {
            const payload = {
                query: query,
                hits: hits
            };

            // Use Beacon if available for reliability on page unload, else fetch
            // Using fetch for simple JSON support with WP REST API
            fetch(config.apiUrl + '/log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            }).catch(e => console.error('Log error', e));
        }, 2000); // 2s debounce for logging
    }

})();
