( function( $ ) {

	"use strict";

	var servicesListPublic = {

		settings: {
			selectors: {
				main: '.services-container',
				filter: '.cherry-services-filter',
				loadMore: '.ajax-more-btn',
				pager: '.services-ajax-pager',
				filterLink: '.cherry-services-filter_link',
				result: '.services-listing',
				container: '.cherry-services',
				loader: '.services-loader',
			},
			actions: {
				filter: 'cherry_services_filter_posts',
				more: 'cherry_services_load_more',
				pager: 'cherry_services_pager'
			},
			templates: {
				loaderLarge: '<div class="services-loader loader-large">' + window.cherryServices.loader + '</div>',
				loaderSmall: '<div class="services-loader loader-small">' + window.cherryServices.loader + '</div>',
			},
			state: {
				filters: false,
				more: false,
				pager: false
			}

		},

		init: function () {

			var self = this;

			// Document ready event check
			if ( CherryJsCore.status.is_ready ) {
				self.render( self );
			} else {
				CherryJsCore.variable.$document.on( 'ready', self.render( self ) );
			}

		},

		render: function ( self ) {

			if ( ! window.elementorFrontend ) {
				$( self.settings.selectors.main ).each( function() {
					self.initFilters( $( this ) );
					self.initLoadMore( $( this ) );
					self.initPager( $( this ) );
				} );
			} else {
				$( window ).on( 'elementor/frontend/init', self.initElementorWidget );
			}

		},

		initElementorWidget: function() {

			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/cherry_services.default',
				function( $scope ) {

					var $container = $scope.find( servicesListPublic.settings.selectors.main );

					if ( $container.length ) {

						if ( window.elementorFrontend.isEditMode() ) {
							servicesListPublic.settings.state.filters = false;
							servicesListPublic.settings.state.more    = false;
							servicesListPublic.settings.state.pager   = false;
						}

						servicesListPublic.initFilters( $container );
						servicesListPublic.initLoadMore( $container );
						servicesListPublic.initPager( $container );

					}

				}
			);

		},

		addLoader: function( $container, isMore ) {

			var template = this.settings.templates.loaderSmall;

			if ( false === isMore ) {
				$container.addClass( 'in-progress' );
				template = this.settings.templates.loaderLarge;
			}

			$container.append( template );
		},

		removeLoader: function( $container, isMore ) {

			if ( false === isMore ) {
				$container.removeClass( 'in-progress' );
			}

			$container.find( this.settings.selectors.loader ).remove();
		},

		initFilters: function( $item ) {

			if ( false !== servicesListPublic.settings.state.filters ) {
				return;
			}

			servicesListPublic.settings.state.filters = true;

			var $filter    = $item.find( servicesListPublic.settings.selectors.filter ),
				$result    = $item.find( servicesListPublic.settings.selectors.result ),
				$container = $item.find( servicesListPublic.settings.selectors.container ),
				data       = new Object();

			$filter.on( 'click', servicesListPublic.settings.selectors.filterLink, function( event ) {

				var $this   = $( this ),
					$parent = $this.parent();

				event.preventDefault();

				if ( $parent.hasClass( 'active' ) ) {
					return;
				}

				data.cat    = $this.data( 'term' );
				data.atts   = $container.data( 'atts' );
				data.cats   = $container.data( 'cat' );
				data.action = servicesListPublic.settings.actions.filter;

				$parent.addClass( 'active' ).siblings().removeClass( 'active' );
				servicesListPublic.addLoader( $container, false );

				$.ajax({
					url: window.cherryServices.ajaxurl,
					type: 'post',
					dataType: 'json',
					data: data,
					error: function() {
						servicesListPublic.removeLoader( $container, false );
					}
				}).done( function( response ) {
					servicesListPublic.removeLoader( $container, false );
					$result.html( response.data.result );
					$container.data( 'atts', response.data.atts );
					$container.data( 'page', 1 );
					$container.data( 'pages', response.data.pages );

					if ( 1 < response.data.pages && $( servicesListPublic.settings.selectors.loadMore, $item ).length ) {
						$( servicesListPublic.settings.selectors.loadMore, $item ).removeClass( 'btn-hidden' );
					}

					if ( 1 == response.data.pages && $( servicesListPublic.settings.selectors.loadMore, $item ).length ) {
						$( servicesListPublic.settings.selectors.loadMore, $item ).addClass( 'btn-hidden' );
					}

					if ( $( servicesListPublic.settings.selectors.pager, $item ).length ) {
						$( servicesListPublic.settings.selectors.pager, $item ).remove();
					}

					$container.append( response.data.pager );

				});
			});
		},

		initLoadMore: function( $item ) {

			if ( false !== servicesListPublic.settings.state.more ) {
				return;
			}

			servicesListPublic.settings.state.more = true;

			$item.on( 'click', servicesListPublic.settings.selectors.loadMore, function( event ) {

				var $this      = $( this ),
					$result    = $item.find( servicesListPublic.settings.selectors.result ),
					$container = $item.find( servicesListPublic.settings.selectors.container ),
					pages      = $container.data( 'pages' ),
					data       = new Object();

				event.preventDefault();

				data.page   = $container.data( 'page' );
				data.atts   = $container.data( 'atts' );
				data.action = servicesListPublic.settings.actions.more;

				servicesListPublic.addLoader( $container, true );

				$.ajax({
					url: window.cherryServices.ajaxurl,
					type: 'post',
					dataType: 'json',
					data: data,
					error: function() {
						servicesListPublic.removeLoader( $container, true );
					}
				}).done( function( response ) {
					servicesListPublic.removeLoader( $container, true );
					$result.append( response.data.result );
					$container.data( 'page', response.data.page );

					if ( response.data.page == pages ) {
						$this.addClass( 'btn-hidden' );
					}

				});

			});

		},

		initPager: function( $item ) {

			if ( false !== servicesListPublic.settings.state.pager ) {
				return;
			}

			servicesListPublic.settings.state.pager = true;

			$item.on( 'click', servicesListPublic.settings.selectors.pager + ' a.page-numbers', function( event ) {

				var $this      = $( this ),
					$result    = $item.find( servicesListPublic.settings.selectors.result ),
					$container = $item.find( servicesListPublic.settings.selectors.container ),
					pages      = $container.data( 'pages' ),
					data       = new Object();

				event.preventDefault();

				data.page   = $this.data( 'page' );
				data.atts   = $container.data( 'atts' );
				data.action = servicesListPublic.settings.actions.pager;

				servicesListPublic.addLoader( $container, false );

				$this.addClass( 'current' ).siblings().removeClass( 'current' );

				$.ajax({
					url: window.cherryServices.ajaxurl,
					type: 'post',
					dataType: 'json',
					data: data,
					error: function() {
						servicesListPublic.removeLoader( $container, false );
					}
				}).done( function( response ) {

					servicesListPublic.removeLoader( $container, false );
					$result.html( response.data.result );
					$container.data( 'page', response.data.page );

				});

			});

		}

	}

	servicesListPublic.init();

}( jQuery ) );
