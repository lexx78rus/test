if (!window.WTVDVS) {
    window.WTVDVS = {
        render_iframe:function (params) {
            //conflict A begin
            //var height = params.height, width = params.width;
            //delete params.height;
            //delete params.width;
            //var sIframe = '<iframe src="' + params.iframeUrl + '/parent_' + encodeURI(window.location.pathname + //window.location.search) + '"></iframe>';
            //conflict A end

            //conflict B begin
            var sParentUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + window.location.search;
            var height = params.height, width = params.width;
            delete params.height;
            delete params.width;
            var sIframe = '<iframe frameborder="0" width="100%" height="100%" src="' + params.iframeUrl + 'parent_' + this.encode_base64(encodeURIComponent(sParentUrl)) + '/"></iframe>';
            //conflict B end
            
            var wrapper = document.getElementById(params.id);
            if (wrapper) {
                wrapper.innerHTML = sIframe, wrapper.style.width = parseInt(width) + 'px', wrapper.style.height = parseInt(height) + 'px', wrapper.style.padding = 0, wrapper.style.display = 'block';
            } else if (window.console && console.error)console.error('TrialPay: Could not find DOM element with ID: ' + id)
//conflict C begin 
//(blank)
//conflict C end
//conflict D begin
        },

        encode_base64: function(data) {
            var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
            var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
                ac = 0,
                enc = '',
                tmp_arr = [];

            if (!data) {
                return data;
            }

            do { // pack three octets into four hexets
                o1 = data.charCodeAt(i++);
                o2 = data.charCodeAt(i++);
                o3 = data.charCodeAt(i++);

                bits = o1 << 16 | o2 << 8 | o3;

                h1 = bits >> 18 & 0x3f;
                h2 = bits >> 12 & 0x3f;
                h3 = bits >> 6 & 0x3f;
                h4 = bits & 0x3f;

                // use hexets to index into b64, and append result to encoded string
                tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
            } while (i < data.length);

            enc = tmp_arr.join('');

            var r = data.length % 3;

            return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
//conflict D end
        }
    }
}