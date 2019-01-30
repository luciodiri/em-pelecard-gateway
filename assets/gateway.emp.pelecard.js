//add Pelecard redirection
$(document).bind('em_booking_gateway_add_emp_pelecard', function(event, response){
	// called by EM if return JSON contains gateway key, notifications messages are shown by now.
	if(response.result && typeof response.emp_pelecard_url != 'undefined' ){
		var ppForm = $('<form action="'+response.emp_pelecard_url+'" method="post" id="em-pelecardv-redirect-form"></form>');
		$.each( response.paypal_vars, function(index,value){
			ppForm.append('<input type="hidden" name="'+index+'" value="'+value+'" />');
		});
		ppForm.append('<input id="em-pelecard-submit" type="submit" style="display:none" />');
		ppForm.appendTo('body').trigger('submit');
	}
});