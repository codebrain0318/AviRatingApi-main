var Utils = []

String.prototype.initCap = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
}

$.fn.serializeObject = function(){
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

String.prototype.initCap = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
}

Utils.isUrlValid = function(url) {
	return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
}

Utils.toMillions = function(number){
	splitAt = 3;
	//number = Math.round(number, 2);
	number = number.toString().split('').reverse().join("");
	n = number.split('.');

	number = n[n.length-1];
	number = number.match(new RegExp('.{1,'+splitAt+'}', 'g'));
	r = '';
	for(i=number.length-1; i>0; i--){
	    number[i] = number[i].split('').reverse().join("");
		r += number[i]+',';
	};
    number[i] = number[i].split('').reverse().join("");
	r += number[i];
	if(n.length == 2)
		r+='.'+n[0];
	return r;
};

Utils.get = function(url, success, input, error, extras){
	if(typeof extras === "undefined"){
		extras = {type: 'get'}
	} else {
		extras['type'] = 'get';
	}

	Utils.post(url, success, input, error, extras);
}

Utils.post = function(url, success, input, error, extras){
	reqOptions = {};
	if(typeof extras === "undefined"){
		extras = {};
	}

	if(typeof extras['dataType'] === "undefined"){
		extras['dataType'] = 'json';
	}

	if(typeof extras['type'] === "undefined"){
		extras['type'] = 'post';
	}
	
	extras['url'] = url;
	extras['data'] = input;
	extras['headers'] = {
		'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	}

	extras['success'] = function(data, status, xhr){
      	Utils.stopWait();
      	//console.log('response xhr', xhr);
      	if(typeof success === "undefined" || success == "default"){
      		Utils.msgSuccess('Action performed Successfully');
      	} else{
        	success(data, status, xhr);
      	}
   	};

   	extras['error'] = function(xhr, textStatus, errorThrown){
      	Utils.stopWait();
      	console.log('data', textStatus);
      	
      	if(typeof error === "undefined" || error == "default"){
      		console.log('undetermined response');
         	// Utils.msgError("Some error has occured!");
      	} else{
         	error(xhr, textStatus, errorThrown);
      	}
   	};
   	reqOptions = extras;
  	Utils.startWait();
    $.ajax(extras);
    return false;
}

Utils.msgSuccess = function(msg){
	msgSuccess(msg)
}

Utils.msgWarning = function(msg){
	msgWarning(msg);
}

Utils.msgError = function(msg){
	msgError(msg);
}

Utils.startWait = function() {
   $(".loader").show();
   $(".inner-body").addClass('wait')
};

Utils.stopWait = function() {
   $(".loader").hide();
   $(".inner-body").removeClass('wait')
};

$.addParamToUrl = function(param, value) {

	let split = window.location.search.replace('?', '')
   	if(split.length > 0){
   		split = split.split('&')
   	}

	var obj = {};
	for(var i = 0; i < split.length; i++){
	    var kv = split[i].split('=');
	    if(kv[0] != 'region'){
	    	obj[kv[0]] = decodeURIComponent(kv[1] ? kv[1].replace(/\+/g, ' ') : kv[1]);
	    }
	}
	obj[param] = value
	
	url = `${window.location.protocol}//${window.location.host}${window.location.pathname}?${$.param(obj)}`
	return url;
}

$(function() {
    $(".loader").fadeOut(1000, function() {
        $(".inner-body").fadeIn(500)
    })

    // $('.k-datepicker').datepicker({
    //     duration: "slow",
    //     format: 'yyyy-mm-dd',
    // })

    // HOVER BOOTSTRAP 4 MENU
    $('ul.navbar-nav li.dropdown').hover(function() {
		$(this).addClass('show')
		$(this).find('.dropdown-menu').addClass('show')
	  	$(this).find('.dropdown-menu').stop(true, true).delay(200).fadeIn(500);
	}, function() {
		$(this).removeClass('show')
		$(this).find('.dropdown-menu').removeClass('show')
	  	$(this).find('.dropdown-menu').stop(true, true).delay(200).fadeOut(500);
	});
})