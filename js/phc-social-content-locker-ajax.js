jQuery(document).ready( function($){
	Feedback= {
		'container': null,
		'default': function(data){
			console.log("Default");
		},
		'check_has_registered_or_not': function(res){
			if( res.type == "success" ){
				window.location.reload();
			}else if( res.type == "relogin" ){
				window.location.reload();
			}else{
				$(res.msg).appendTo(phc_social_content_locker_params.feedback_selector);
			}
		},
	}

	Ajax= {
		'type': "default",
		send: function(url, data){
			AjaxObj= this;
			$.ajax({
				dataType: 'json',
				type: 'POST',
				url: url,
				data: data,
                beforeSend: function(){
					AjaxObj.blockUI();
                },
                complete: function(){
					AjaxObj.unBlock();
                },
				success: function(data){
					type= AjaxObj.type;
					Feedback[type](data);
				}
			});
		},
		blockUI: function(){
			$.blockUI({ 
			message: "",
			});
		},
		unBlock: function(){
			$.unblockUI(); 
		},		
	}
	
	Facebook= {
		getLoginStatus: function(){
			var stat= 1;
			FacebookObj= this;
			FB.getLoginStatus( function(response){
				if( response.status === 'connected' ){
					// connected
				}else if(response.status === 'not_authorized') {
					// not_authorized
					stat= 0;
				}else{	
					// not_logged_in
					stat= 0;
				}
			});
			return stat;
		},
		relogin: function(){
			FB.api('/me', function(response){
				response.from= "facebook";
				response.complete_dialog= false;
				response.route= '<?php echo $fbset["route"]; ?>';
				url= '<?php echo $fbset_url["relogin"]; ?>';
				Ajax.type= "fblogin";
				Ajax.send(url, response);
			});
		},
		check_has_registered_or_not: function(response){
			console.log(response);
			FB.api('/me', function(response){
				// Send Request to Facebook
				response.type= "check_has_registered_or_not";
				response.action= phc_social_content_locker_params.action;
				Feedback.selector= phc_social_content_locker_params.feedback_selector;
				Ajax.type= "check_has_registered_or_not";
				Ajax.send(phc_social_content_locker_params.ajaxurl, response);
			});
		}
	}

	$('.fb-login').live('click', function(e){
		e.preventDefault();
		$obj= $(this);
		FB.login( function(response){
			status= Facebook.getLoginStatus();
			if( response.authResponse ){
				// Check Customer has been registered or not
				Feedback.container= $obj;
				Facebook.check_has_registered_or_not(response);
			}else{
				// User cancelled login or did not fully authorize.
			}
		}, { scope: 'email, user_birthday, user_location, user_hometown, publish_actions, publish_stream' });
	});
	
	// Remove lazysocialbuttons
	$('#social-content-locker').find('.lazysocialbuttons').remove();
});