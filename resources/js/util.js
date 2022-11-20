const { default: axios } = require("axios");

class Util {

    constructor() {

        this.priceFormatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        });

        this.numberFormatter = new Intl.NumberFormat('en-US', {});
    }

    getParam(name) {
        var url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    getProperty(name) {
        return $(`[${name}]`).attr(name);
    }

    formatMoney(p) {
        console.log('fm', p);
        return this.priceFormatter.format(p);
    }

    formatNumber(n) {
        return this.numberFormatter.format(n);
    }

    formatDateTime(d) {
        var day = dayjs(d);
        var now = dayjs();
        if(day.format('YYYY-MM-DD') == now.format('YYYY-MM-DD'))
            return day.format('[Today at] h:mma');
            
        return day.format('MMM D [at] h:mma');
    }

    formatDate(d) {
        var day = dayjs(d);
        var now = dayjs();
        return day.format('MMM D, YYYY');
    }

    clone(obj) {
        if(Object.keys(obj).length === 0)
            return {};
            
        return JSON.parse(JSON.stringify(obj));
    }

    getCustomerName(c) {
        if(c) return `${c.first_name} ${c.last_name}`;
        return '';
    }

    getCustomerLink(c) {
        if(c) return `/admin/customers/${c.id}`;
        return '';
    }


    showAsyncStatus(status, error) {
        let $elem = $('#async-status');
        $elem.find('span').text(status);
        $elem.removeClass('error').addClass('show');

        if(error) {
            $elem.addClass('error');
        }
        else {
            setTimeout(function() {
                $elem.removeClass('show');
            }, 3000);
        }
    }

    checkChanged(a, b, ignore) {
    
        if(a == null) a = '';
        if(b == null) b = '';
        if(ignore == null) ignore = [];

        if(Array.isArray(a)) {
        
            if(a.length != b.length) 
                return true;

            // We have to do a recursive check on the array elements.
            for(let i = 0; i < a.length; i++) {
                if(this.checkChanged(a[i], b[i], ignore)) {
                    return true;
                }
            }
        }

        // Do a recursive check on nested objects.
        else if(typeof a === 'object' && a != null) {
          
            for(let k in a) {
                if(ignore.indexOf(k) >= 0) continue;
                if(this.checkChanged(a[k], b[k], ignore))
                    return true;
            }

            for(let k in b) {
                if(ignore.indexOf(k) >= 0) continue;
                if(this.checkChanged(a[k], b[k], ignore))
                    return true;
            }
        }

        else if(a != b) {
            return true;
        }

        return false;
    }
}

window.Util = new Util;
