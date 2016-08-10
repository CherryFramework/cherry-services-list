(function($){
	"use strict";

	CherryJsCore.utilites.namespace('services_admin_theme_script');
	CherryJsCore.services_admin_theme_script = {
		ajaxRequest: null,
		ajaxRequestSuccess: true,
		init: function () {
			var self = this;

			if( CherryJsCore.status.is_ready ){
				self.readyRender( self );
			}else{
				CherryJsCore.variable.$document.on( 'ready', self.readyRender( self ) );
			}

		},
		readyRender: function ( self ) {

			var self = self,
				$servicesOptionsForm = $('#cherry-services-options-form'),
				$saveButton = $('#cherry-services-save-options', $servicesOptionsForm ),
				$defineAsDefaultButton = $('#cherry-services-define-as-default', $servicesOptionsForm ),
				$restoreButton = $('#cherry-services-restore-options', $servicesOptionsForm );

				$saveButton.on( 'click', {
					self: self,
					optionsForm: $servicesOptionsForm,
					ajaxRequestType: 'save'
				}, self.ajaxRequest );

				$defineAsDefaultButton.on( 'click', {
					self: self,
					optionsForm: $servicesOptionsForm,
					ajaxRequestType: 'define_as_default'
				}, self.ajaxRequest );

				$restoreButton.on( 'click', {
					self: self,
					optionsForm: $servicesOptionsForm,
					ajaxRequestType: 'restore'
				}, self.ajaxRequest );

		},
		ajaxRequest: function( event ) {

			var self = event.data.self,
				$servicesOptionsForm = event.data.optionsForm,
				$cherrySpinner = $('.cherry-spinner-wordpress', $servicesOptionsForm),
				ajaxRequestType = event.data.ajaxRequestType,
				serializeArray = $servicesOptionsForm.serializeObject(),
				data = {
					nonce: CherryJsCore.variable.security,
					action: 'cherry_services_process_options',
					post_array: serializeArray,
					type: ajaxRequestType
				};

			if ( ! self.ajaxRequestSuccess ) {
				self.ajaxRequest.abort();
				self.noticeCreate( 'error-notice', cherryServicesSettings.please_wait_processing );
			}

			self.ajaxRequest = jQuery.ajax( {
				type: 'POST',
				url: ajaxurl,
				data: data,
				cache: false,
				beforeSend: function(){
					self.ajaxRequestSuccess = false;
					$cherrySpinner.fadeIn();
				},
				success: function( response ) {
					self.ajaxRequestSuccess = true;
					$cherrySpinner.fadeOut();
					self.noticeCreate( response.type, response.message );
					if ( 'restore' === ajaxRequestType ) {
						window.location.href = cherryServicesSettings.redirect_url;
					}
				},
				dataType: 'json'
			} );

			return false;
		},
		noticeCreate: function( type, message ) {
			var
				notice = $('<div class="notice-box ' + type + '"><span class="dashicons"></span><div class="inner">' + message + '</div></div>'),
				rightDelta = 0,
				timeoutId;

			$('body').prepend( notice );
			reposition();
			rightDelta = -1 * ( notice.outerWidth( true ) + 10 );
			notice.css( {'right' : rightDelta } );

			timeoutId = setTimeout( function () { notice.css( {'right' : 10 } ).addClass('show-state') }, 100 );
			timeoutId = setTimeout( function () {
				rightDelta = -1 * ( notice.outerWidth( true ) + 10 );
				notice.css( { right: rightDelta } ).removeClass( 'show-state' );
			}, 4000 );
			timeoutId = setTimeout( function () {
				notice.remove(); clearTimeout( timeoutId );
			}, 4500 );

				function reposition(){
					var topDelta = 100;

					$( '.notice-box' ).each( function( index ) {
						$( this ).css( { top: topDelta } );
						topDelta += $( this ).outerHeight( true );
					} );
				}
		}
	}
	CherryJsCore.services_admin_theme_script.init();
}(jQuery));

