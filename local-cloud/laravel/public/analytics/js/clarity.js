function parseDate(input) {
	if(input!=''){
    	var parts = input.match(/(\d+)/g);       
    	return new Date(parts[2], parts[0]-1, parts[1]); // months are 0-based
  	} else {
    	return '';
	}
}

function getCookie(cookieName) {
	var cookiePattern = new RegExp('(^|;)[ ]*' + cookieName + '=([^;]*)'),
		cookieMatch = cookiePattern.exec(document.cookie);
		if(cookieMatch){
			return cookieMatch[2];
		}
		return 0;
};

function setCookie(cookieName, value, daysToExpire, path, domain, secure) {
	var expiryDate;

	if (daysToExpire) {
		expiryDate = new Date();
		expiryDate.setTime(expiryDate.getTime() + (daysToExpire * 8.64e7));
	}

	document.cookie = cookieName + '=' + (value.toString()) +
	(daysToExpire ? ';expires=' + expiryDate.toGMTString() : '') +
	';path=' + (path ? path : '/') +
	(domain ? ';domain=' + domain : '') +
	(secure ? ';secure' : '');
};


jQuery.expr[':'].regex = function(elem, index, match) {
    var matchParams = match[3].split(','),
        validLabels = /^(data|css):/,
        attr = {
            method: matchParams[0].match(validLabels) ? 
                        matchParams[0].split(':')[0] : 'attr',
            property: matchParams.shift().replace(validLabels,'')
        },
        regexFlags = 'ig',
        regex = new RegExp(matchParams.join('').replace(/^\s+|\s+$/g,''), regexFlags);
    return regex.test(jQuery(elem)[attr.method](attr.property));
}

addCommas = function(nStr) {
  var rgx, x, x1, x2;
  nStr += '';
  x = nStr.split('.');
  x1 = x[0];
  x2 = '';
  if (x.length > 1) {
    x2 = '.' + x[1];
  }
  rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1)) {
    x1 = x1.replace(rgx, '$1' + ',' + '$2');
  }
  return x1 + x2;
};

formatNumber = function(num) {
  return addCommas(num.toFixed(0));
};