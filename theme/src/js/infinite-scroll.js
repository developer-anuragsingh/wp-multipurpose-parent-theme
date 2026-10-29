/* global DOMParser, IntersectionObserver */

/**
 * Atlas Archive Infinite Scroll — Interactivity API store.
 *
 * Namespace: atlas/infinite-scroll. Progressively enhances WordPress archive
 * and query block pagination. Preserves native core/query-pagination for zero-JS
 * visitors and search crawlers, while hydrating a high-performance IntersectionObserver
 * and Load More mechanism for clients with JavaScript enabled. Respects
 * prefers-reduced-motion by falling back to manual click-to-load.
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * WeakMap tracking active IntersectionObservers per sentinel element.
 *
 * @type {WeakMap<HTMLElement, IntersectionObserver>}
 */
const activeObservers = new WeakMap();

store( 'atlas/infinite-scroll', {
	state: {
		/**
		 * Whether an asynchronous page fetch is currently in progress.
		 *
		 * @return {boolean} True if loading.
		 */
		get isLoading() {
			return !! getContext().loading;
		},

		/**
		 * Current button label text based on loading state.
		 *
		 * @return {string} Button text.
		 */
		get buttonText() {
			const context = getContext();
			if ( context.loading ) {
				return context.labelLoading || 'Loading more posts…';
			}
			return context.labelLoadMore || 'Load more posts';
		},

		/**
		 * Live region status text for assistive technology announcements.
		 *
		 * @return {string} Status announcement string.
		 */
		get statusText() {
			return getContext().statusText || '';
		},

		/**
		 * Whether additional pages exist to be loaded.
		 *
		 * @return {boolean} True if a valid next URL is present.
		 */
		get hasMore() {
			const context = getContext();
			return !! ( context.hasMore && context.nextUrl );
		},
	},

	actions: {
		/**
		 * Fetch and append the subsequent page of posts.
		 *
		 * Generator action allowing sequential async fetch and DOM manipulation
		 * without external promise or state management dependencies.
		 *
		 * @yield {Promise} The asynchronous fetch, text extraction, and parsing.
		 */
		*loadMore() {
			const context = getContext();
			if ( context.loading || ! context.hasMore || ! context.nextUrl ) {
				return;
			}

			context.loading = true;
			context.statusText = context.labelLoading || 'Loading more posts…';

			try {
				const response = yield fetch( context.nextUrl, {
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
					},
				} );

				if ( ! response.ok ) {
					context.loading = false;
					context.statusText =
						context.labelError || 'Error loading posts.';
					return;
				}

				const html = yield response.text();
				const parser = new DOMParser();
				const doc = parser.parseFromString( html, 'text/html' );

				const { ref } = getElement();
				const queryContainer = ref.closest( '.wp-block-query' );
				if ( ! queryContainer ) {
					context.loading = false;
					return;
				}

				const currentPostList = queryContainer.querySelector(
					'.wp-block-post-template'
				);
				const nextPostList = doc.querySelector(
					'.wp-block-post-template'
				);

				if ( currentPostList && nextPostList ) {
					while ( nextPostList.firstChild ) {
						currentPostList.appendChild( nextPostList.firstChild );
					}
				}

				const nextPaginationLink = doc.querySelector(
					'.wp-block-query-pagination-next a'
				);
				if ( nextPaginationLink && nextPaginationLink.href ) {
					context.nextUrl = nextPaginationLink.href;
					context.hasMore = true;
				} else {
					context.nextUrl = null;
					context.hasMore = false;
				}

				context.statusText = context.hasMore
					? context.labelLoaded || 'More posts loaded.'
					: context.labelEnd || 'All posts loaded.';

				if (
					window.history &&
					window.history.replaceState &&
					context.nextUrl
				) {
					window.history.replaceState( {}, '', context.nextUrl );
				}
			} catch {
				context.statusText =
					context.labelError || 'Error loading posts.';
			} finally {
				context.loading = false;
			}
		},
	},

	callbacks: {
		/**
		 * Initialize component on hydration.
		 *
		 * Marks the parent query block as interactive (allowing CSS to hide
		 * standard pagination links) and unhides the manual trigger button.
		 *
		 * @return {void}
		 */
		init() {
			const { ref } = getElement();
			const queryContainer = ref.closest( '.wp-block-query' );
			if ( queryContainer ) {
				queryContainer.classList.add( 'has-atlas-infinite-scroll' );
			}

			const button = ref.querySelector(
				'.atlas-infinite-scroll__button'
			);
			if ( button ) {
				const context = getContext();
				if ( context.hasMore && context.nextUrl ) {
					button.removeAttribute( 'hidden' );
				}
			}
		},

		/**
		 * Observe the sentinel element for automated scroll-triggered loading.
		 *
		 * Bypasses automated scroll loading when prefers-reduced-motion is active
		 * or when IntersectionObserver is unsupported, safely degrading to click.
		 *
		 * @return {(() => void)|void} Cleanup callback to disconnect observer.
		 */
		observeSentinel() {
			if (
				window.matchMedia &&
				window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
			) {
				return;
			}

			if ( ! ( 'IntersectionObserver' in window ) ) {
				return;
			}

			const { ref } = getElement();
			const context = getContext();
			const observer = new IntersectionObserver(
				( entries ) => {
					const [ entry ] = entries;
					if (
						entry.isIntersecting &&
						! context.loading &&
						context.hasMore &&
						context.nextUrl
					) {
						store( 'atlas/infinite-scroll' ).actions.loadMore();
					}
				},
				{
					rootMargin: '300px 0px',
				}
			);

			observer.observe( ref );
			activeObservers.set( ref, observer );

			return () => {
				observer.disconnect();
				activeObservers.delete( ref );
			};
		},
	},
} );
