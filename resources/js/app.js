document.querySelectorAll('[data-movie-poster]').forEach((poster) => {
    const placeholder = poster.parentElement?.querySelector(
        '[data-poster-placeholder]',
    );

    if (!placeholder) {
        return;
    }

    const showPlaceholder = () => {
        poster.hidden = true;
        placeholder.hidden = false;
    };

    poster.addEventListener('error', showPlaceholder, { once: true });

    if (poster.complete && poster.naturalWidth === 0) {
        showPlaceholder();
    }
});

const searchResultsUrlKey = 'movieSearchResultsUrl';

document.querySelectorAll('[data-movie-link]').forEach((movieLink) => {
    movieLink.addEventListener('click', () => {
        sessionStorage.setItem(searchResultsUrlKey, window.location.href);
    });
});

const backToSearchLink = document.querySelector('[data-back-to-search]');
const searchResultsUrl = sessionStorage.getItem(searchResultsUrlKey);

if (backToSearchLink && searchResultsUrl) {
    backToSearchLink.href = searchResultsUrl;
}
