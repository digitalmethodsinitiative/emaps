var _styles = ["font-size:10px;color:#777777;",
"font-size:12px;color:#777777;",
"font-size:14px;color:#666666;",
"font-size:16px;color:#666666;",
"font-size:18px;color:#555555;",
"font-size:20px;color:#555555;",
"font-size:22px;color:#444444;",
"font-size:24px;color:#444444;",
"font-size:26px;color:#333333;",
"font-size:28px;color:#333333;",
"font-size:30px;color:#222222;",
"font-size:32px;color:#222222;",
"font-size:34px;color:#000000;",
"font-size:36px;color:#000000;"]

var _biggest = 0;
var _smallest = 100000000000000;
                                                                                            
function makeCloudNumber(num) {
    _layout = $("#layout input:checked").val();
    _order = $("#order input:checked").val();
    _width = $("#size_width").val();
    _case = $("#case input:checked").val();
                                                                                    		
    createCloud(_order,_layout,_width,_case, num);
}
                                                                                            	
function interfaceChange() {
                                                                                    		
    _layout = $("#layout input:checked").val();
    _order = $("#order input:checked").val();
    _width = $("#size_width").val();
    _case = $("#case input:checked").val();
                                                                                    		
    /* Old */
    //createCloud(_order,_layout,_width,_case);
                                                                                                    
    for (i = 1; i <= document.clouds; i++) {
        createCloud(_order,_layout,_width,_case,i);
    }
}
                                                                                    	
                                                                                    	
function createCloud(_order,_layout,_width,_case,num) {
                                                                                    		
    // old:
    //var _textinput = $("#textinput").val();
    var _textinput = $("#input" + num).html();
                                                                                    		
    var _taglist = {};
    var _textlines = _textinput.split(/[\n\r]/);

                                                                                    		
    if(_textlines[0].match(/:\d/)) {
        for(var i = 0; i < _textlines.length; i++) {
            _textlines[i] = $.trim(_textlines[i]);
            var _tmp = _textlines[i].split(/[,\t:]/);
            _taglist[_tmp[0]] = parseInt(_tmp[1]);
        }
    } else if(_textlines[0].match(/\t\d/)) {
        for(var i = 0; i < _textlines.length; i++) {
            _textlines[i] = $.trim(_textlines[i]);
            var _tmp = _textlines[i].split(/[,\t:]/);
            _taglist[_tmp[0]] = parseInt(_tmp[1]);
        }
    } else {
        for(var i = 0; i < _textlines.length; i++) {
            _textlines[i] = $.trim(_textlines[i]);
            if(typeof(_taglist[_textlines[i]]) == "undefined") {
                _taglist[_textlines[i]] = 1;
            } else {
                _taglist[_textlines[i]]++;
            }
        }
    }
                                                                                    		
                                                                                    		
                                                                                    		
                                                                                    		
    for(var _key in _taglist) {

        if(_taglist[_key] < _smallest) {
            _smallest = _taglist[_key];
        }
                                                                                    			
        if(_taglist[_key] > _biggest) {
            _biggest = _taglist[_key];
        }
    }
                                                                                    	
                                                                                    	
    var _sorted = [];
    for(var _key in _taglist) {
        var _tag = _key;
        if(_case == "upper") {
            var _tag = _key.toUpperCase();
        }
        if(_case == "lower") {
            var _tag = _key.toLowerCase();
        }
        _sorted.push([_tag, _taglist[_key]])
    }
                                                                                    	
    if(_order == 'alpha') {
        _sorted.sort(function(a, b) { 
            if(a[0][0] < b[0][0]) return -1;
            if(a[0][0] > b[0][0]) return 1;
            return 0;
        });
    }
                                                                                    		
    if(_order == 'rank') {
        _sorted.sort(function(a, b) {
            return parseInt(a[1]) - parseInt(b[1])
            })
        _sorted.reverse();
    }
                                                                                    		
    //console.log(_sorted);
                                                                                    	
                                                                                    		
    var _html = "";
                                                                                    		
    for(var i = 0; i < _sorted.length; i++) {
                                                                                    			
        if (_sorted[i][1] >= _smallest) {
                                                                                    			
            var _tmpsize = _sorted[i][1] - _smallest;
                                                                                    		
            if(_tmpsize != 0) {
                _tmpsize = Math.round(_tmpsize / (_biggest - _smallest) * (_styles.length - 1));
            }

            //console.log(_tmpsize);
                                                                                    				
            _sorted[i][0] = _sorted[i][0].replace(/\s/,"&nbsp;");
                                                                                    				
            _html += '<span class="tag" style="'+ _styles[_tmpsize] +'">' + _sorted[i][0] + '&nbsp;(' + _sorted[i][1] + ') </span>';
        }
    }


    // old
    //$("#output").css("width",_width  + "px");
    //$("#output").html(_html);
    $("#output" + num).css("width",_width  + "px");
    $("#output" + num).html(_html);		
                                                                                    	
    if(_layout == "inline") {
        $(".tag").css("display",_layout);
        $(".tag").css("line-height","normal");
        if(_order == 'alpha') {
            $(".tag").css("line-height","35px");
        } else {
            $(".tag").css("line-height","150%");
        }
                                                                                    			
    } else {
        $(".tag").css("display",_layout);
        $(".tag").css("line-height","normal");
        $(".tag").css("line-height","130%");
        $(".tag").css("padding-top","8px");
    }
}

function countlines(id) {
    var text = $(id).val();   
    if (text === '') { return 0; }
    var lines = text.split(/\r|\r\n|\n/);
    return lines.length;
}

function update_estimate() {
    //var g_urls = encodeURIComponent($("#urls").val());
    //var g_issues = encodeURIComponent($("#issues").val());
    var g_urls = $("#urls").val();
    var g_issues = $("#issues").val();
    $.get( "estimate.php", { urls: g_urls, issues: g_issues } )
		.done(function( estimate_seconds ) {
    		estimate_minutes = Math.floor(estimate_seconds / 60);
    		estimate_seconds -= (estimate_minutes * 60);
    		$("#perfestimate").html('Estimated time for query to complete (no guarantees!): ' + estimate_minutes + ' minutes and ' + estimate_seconds + ' seconds');
	});  
}

function update_estimate_debugdev() {
    urls = countlines("#urls");
    issues = countlines("#issues");
    if (urls > 0) {
    	estimate_seconds = Math.floor(0.075 * 60 * issues);
    } else {
        estimate_seconds = 60 * issues;
    }
    estimate_minutes = Math.floor(estimate_seconds / 60);
    estimate_seconds -= (estimate_minutes * 60);
    //<div id="perfestimate">Estimated time for query to complete (without warrenty): n/a</div><br />
    $("#perfestimate").html('Estimated time for query to complete (no guarantees!): ' + estimate_minutes + ' minutes and ' + estimate_seconds + ' seconds');
}

$( document ).ready(function() {

$('#issues').change(function() {
		update_estimate();
});
$('#urls').change(function() {
		update_estimate();
});

     update_estimate();

});
