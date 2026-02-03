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

    // Sort State
    let currentSort = 'relevance'; // or 'created_at:desc'

    // Inject Sort UI if enabled
    if (enableSort) {
        const sortContainer = document.createElement('div');
        sortContainer.className = 'ss-sort-container';
        sortContainer.innerHTML = `
            <select id="ss-sort-select" class="ss-sort-select">
                <option value="relevance">Relevance</option>
                <option value="post_date:desc">Newest First</option>
                <option value="post_date:asc">Oldest First</option>
            </select>
        `;
        // Insert before results
        resultsContainer.insertBefore(sortContainer, hitsContainer);

        document.getElementById('ss-sort-select').addEventListener('change', function (e) {
            currentSort = e.target.value;
            if (input.value.trim().length > 0) {
                performSearch(input.value.trim());
            }
        });
    }

    // Mobile Button logic
    if (config.experience && config.experience.mobile_btn) {
        const btn = document.createElement('div');
        btn.className = 'ss-mobile-btn';
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';

        // Inline styles
        Object.assign(btn.style, {
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            width: '50px',
            height: '50px',
            backgroundColor: '#0073aa',
            borderRadius: '50%',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            cursor: 'pointer',
            boxShadow: '0 4px 10px rgba(0,0,0,0.2)',
            zIndex: '99999'
        });

        document.body.appendChild(btn);

        btn.addEventListener('click', function () {
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => input.focus(), 500);
        });
    }

    let debounceTimer;
    let logTimer;

    input.addEventListener('input', function (e) {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length === 0) {
            resultsContainer.style.display = 'none';
            return;
        }

        loader.style.display = 'block';

        debounceTimer = setTimeout(function () {
            performSearch(query);
        }, 200);
    });

    function performSearch(query) {
        let sortParam = '';
        if (currentSort !== 'relevance') {
            sortParam = `&sort_by=${currentSort}`;
        }

        const numTypos = useTypo ? 2 : 0;

        const url = `${config.protocol}://${config.host}:${config.port}/collections/${config.collection}/documents/search?q=${encodeURIComponent(query)}&query_by=post_title,post_content&per_page=${limit}&num_typos=${numTypos}${sortParam}`;

        fetch(url, {
            method: 'GET',
            headers: {
                'X-TYPESENSE-API-KEY': config.apiKey,
                'Content-Type': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';
                renderHits(data);
            })
            .catch(err => {
                console.error(err);
                loader.style.display = 'none';
            });
    }

    function renderHits(data) {
        resultsContainer.style.display = 'flex';
        hitsContainer.innerHTML = '';

        if (data.found === 0) {
            hitsContainer.innerHTML = '<div class="ss-no-results">No results found.</div>';
            logSearch(input.value.trim(), 0);
            return;
        }

        logSearch(input.value.trim(), data.found);

        data.hits.forEach(hit => {
            const doc = hit.document;
            const el = document.createElement('div');
            el.className = 'ss-hit';

            let html = `<a href="${doc.permalink}" class="ss-hit-link">`;

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

            el.innerHTML = html;
            hitsContainer.appendChild(el);
        });
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
