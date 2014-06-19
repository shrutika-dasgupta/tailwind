<script type="text/javascript">

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

</script>