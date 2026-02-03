(function () {
    'use strict';

    if (typeof swiftSearchVars === 'undefined') {
        return;
    }

    const config = swiftSearchVars;
    const input = document.getElementById('ss-search-input');
    const resultsContainer = document.querySelector('.ss-results-container');
    const hitsContainer = document.getElementById('ss-hits');
    const loader = document.querySelector('.ss-loader');

    if (!input) return;

    let debounceTimer;

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
        const url = `${config.protocol}://${config.host}:${config.port}/collections/${config.collection}/documents/search?q=${encodeURIComponent(query)}&query_by=post_title,post_content&per_page=10`;

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
            return;
        }

        data.hits.forEach(hit => {
            const doc = hit.document;
            const el = document.createElement('div');
            el.className = 'ss-hit';
            el.innerHTML = `
				<a href="${doc.permalink}" class="ss-hit-link">
					${doc.thumbnail_url ? `<img src="${doc.thumbnail_url}" alt="" class="ss-hit-thumb">` : ''}
					<div class="ss-hit-content">
						<h3 class="ss-hit-title">${highlight(hit, 'post_title')}</h3>
						<p class="ss-hit-excerpt">${doc.post_excerpt || ''}</p>
						<span class="ss-hit-price">${doc.price ? '$' + doc.price : ''}</span>
					</div>
				</a>
			`;
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

})();
