jQuery(document).ready(function($)
{
    $("#myTable").tablesorter({
        sortList: [[0,0]],
        cancelSelection: true,
        cssAsc: "headerSortUp",
        cssDesc: "headerSortDown",
        cssHeader: "header"
    });

    $('#menu-drop-down').click(function(){
        var x = document.getElementById("w3_collapse_menu");
        if (x.className.indexOf("w3-show") == -1) {
            x.className += " w3-show";
        } else {
            x.className = x.className.replace(" w3-show", "");
        }
        return false;
    });

    $('.util_make_ajax').click(function(){
        my_id = $(this)[0].id;

        var data = {
            action: fa_script_handler_vars.action,
            sender_id: my_id,
        };

        my_form = my_id + '_form';
        var x = $('#' + my_form).length;
        if (x > 0) {
            var elements = document.getElementById(my_form).elements;
            for(var i = 0; i < elements.length; i++) {
                element = elements[i];
                key = element.name;
                value = element.value;
                if(key !== "") {
                    data[key] = value
                }
            }
        }

        url = fa_script_handler_vars.url_to_my_site;

        $.post(url, data, function(resp, status)
        {
            text = "";

            if(status === "success")
            {
                if(resp.result === "OK")
                {
                    for(var i=0; i< resp.responses.length; i++) {
                        response = resp.responses[i];
                        switch (response.command) {
                            case 'LOAD_HTML':
                                id = '#' + response.target;
                                var x = $(id).length;
                                if (x > 0) {
                                    $(id).html(response.data);

                                    $("#myTable").tablesorter({
                                        sortList: [[0,0]],
                                        cancelSelection: true,
                                        cssAsc: "headerSortUp",
                                        cssDesc: "headerSortDown",
                                        cssHeader: "header"
                                    });
                                } else {
                                    text = 'Error: Target does not exist ' + id +'<br>';
                                }
                                break;
                            default:
                                text = "Error. Undefined command " + response.command + '<br>';
                                break;
                        }
                    }
                }
                else
                {
                    text = "ERROR: Unexpected response.<br>Message from server:<br>Result=" + resp.pay_status + "<br>Message=" + resp.message;
               }
            }
            else
            {
                text = "Undefined error";
            }

            $('#fa_ajax_messages').text(text);
        },'json');
    });

});
