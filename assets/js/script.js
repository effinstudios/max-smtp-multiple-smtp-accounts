jQuery(document).ready(function($){ $('.maxsmtp-pass-wrapper button').click(function(){ $('.maxsmtp-pass-wrapper .dashicons').toggleClass('dashicons-visibility').toggleClass('dashicons-hidden'); $('.maxsmtp-pass-wrapper #smtp_password').attr('type', function(){ if( $(this).attr('type') === 'text' ){ return 'password'; } else { return 'text'; } }); }); });