/**
 * Created by ionut on 29/06/16.
 */


var scanare_agent_key_time = 0;
var scanare_agent_key = "";
var scanare_agent_key_total = 0;
var coduri_agenti = {};
var iFrequency = 10000; // expressed in miliseconds
var myInterval = 0;


var iFrequencyLogout = 5 * 1000;
var myIntervalLogout = 0;


var lastActivity = 0;
var logOuIn = 0;

var userId = 0;
var centruId = 0;
var userName = "";
var agentiCentru = [];
var ruteCentru = [];
var totalPuisori = 0;
var totalCoduri = 0;
var barcodesArray = [];
var serviceTimmmer = null;
var sendInProgress = false;
var coduri_deteriorate = 0;
var coduri_rem = 0;

var breadcrumb_arr = [];
var lastLevel = 0;
var isOnLine = true;
var topLogout = false;
var lastActivityTime = 0;

$( document ).ready(function() {
    startLoop();
    startLogoutLoop();


    $('#select_3_9_14').on( "change", function(event) {
        $('#select_3_9_15').val(0);
        $('.selectpicker').selectpicker('refresh');
    });

    $('body').on( "change", function(event) {
        lastActivityTime = Date.now();
    });

    $('.delogare_link').on( "click", function(event) {
        event.preventDefault();
        $('.stop-scan').addClass('logout_after');
        $('.stop-scan').click();
        trimiteBorderouri();
        $(this).hide();
    });



    $( "div" ).mousemove(function( event ) {
        lastActivityTime = Date.now();
    });

    /*
    if(window.location.hash) {
        var hash = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
        console.log (hash);
        // hash found
    } else {
        // No hash found
    }
    */
    // console.log(localStorage.getItem('menuTree'));

    $('.app-menu  a.menu-acction').on( "click", function(event) {
        event.preventDefault();
        //$( this ).parent().find('ul').toggleClass('hide-menu');
        //console.log ($(this).attr('level'));
        //console.log ($(this).attr('rel'));
        $('.app-menu .level-'+ $(this).attr('level')).addClass('hide-menu');
        $('.app-menu .sub-path-'+$(this).attr('menu_path')).toggleClass('hide-menu');
        if(isNaN(lastLevel)){
            lastLevel = 0;
        }

        lastLevel++;
        localStorage.setItem('lastLevel',lastLevel);
        breadcrumb_arr[lastLevel] = $(this).text();

        // console.log(breadcrumb_arr);
        // console.log ("level:"+$(this).attr('level') + "     menu_path:"+ $(this).attr('menu_path') + " breadcrumb_arr: "+breadcrumb_arr[lastLevel] + " text:"+$(this).text() + " lastLevel:" + lastLevel);

        localStorage.setItem('prev_path', localStorage.getItem('menu_path'));
        setCurrentPath($(this).attr('menu_path'));
        $('.footer-app-acction').removeClass('hide-menu');
        $('.footer-app-acction a.prev-page').css('display','block');

        if($('.next_scan').is(':visible'))
        {
            $('.start-scan').css('display','block');
        }

        switch($(this).attr('menu_path')) {

            case '1_1':
                if(scan_agent){
                    $('.path-1_1_1').addClass('hide-position');
                    $('.scanare-agent').css('display','block');
                    $('#scanare_agent').focus();
                }
                break;
            case '1_2':
                if(scan_agent){
                    $('.path-1_2_4').addClass('hide-position');
                    $('.scanare-agent').css('display','block');
                    $('#scanare_agent').focus();
                }
                break;
            case '2_5':
            case '2_6':
            case '2_7':
                $('.start-scan').css('display','block');
                break;
            case '2_8':
                $('.app-reweight-area').toggleClass('hide-menu');
                $('.start-scan').css('display','none');
                $('#reweight_cod').focus();
                break;
            case '3':
                $('.start-scan').css('display','block');
                $('.path-3_9_14').removeClass('hide-menu');
                $('.path-3_9_15').removeClass('hide-menu');
                if(scan_agent){
                    $('.path-3_9_14').addClass('hide-position');
                    $('.scanare-agent').css('display','block');
                    $('#scanare_agent').focus();
                }
                break;
            default:

        }




        breadcrumbChange();



    });





    $('.app-menu  a.prev-page').on( "click", function(event) {
        event.preventDefault();



        $('.scanare-agent').css('display','none');
        $('.start-scan').css('display','none');
        $('.app-reweight-area').addClass('hide-menu');


        if(localStorage.getItem('menu_path') == localStorage.getItem('prev_path')){
            localStorage.removeItem('prev_path');
        }

        breadcrumb_arr[lastLevel] = null;

        if(lastLevel > 0){
            lastLevel--;
        }


        localStorage.setItem('lastLevel',lastLevel);
        // delete breadcrumb['lastLevel']

        localStorage.removeItem('savedSelections');

        // console.log(breadcrumb);

        var menu_path = localStorage.getItem('prev_path');
        if(menu_path != 'null' && menu_path){
            $('ul.app-menu > li').addClass('hide-menu');
            $('.app-menu .sub-path-' + menu_path).removeClass('hide-menu');
            $('.footer-app-acction a.prev-page').css('display','block');
            localStorage.setItem('menu_path',menu_path);
            $('.footer-app-acction a.prev-page').css('display','block');
        } else {
            $('ul.app-menu > li').addClass('hide-menu');
            $('.app-menu .level-0').removeClass('hide-menu');
            $('.footer-app-acction a.prev-page').css('display','none');
            setCurrentPath(null);
        }
        $('.footer-app-acction').removeClass('hide-menu');

        breadcrumbChange();


        if(true){
            $('#reweight_cod').val('');
            $('#reweight_kg').val('');
            $('#reweight_lungime').val('');
            $('#reweight_latime').val('');
            $('#reweight_inaltime').val('');
        }
        //changeTile();
    });


    $('.app-menu  a.start-scan').on( "click", function(event) {
        event.preventDefault();

        var selects = $(this).parent().parent().find('li:visible select');


        var selectedOptionsNum = 0;
        var OptionsNum = 0;
        var stringBreadCrumb = '';
        $.each( selects, function( key, value ) {
            OptionsNum++;
            var selectId = $(value).attr('id');



            var selValue = $(value).val();
            var selectedText = $('#'+selectId+" option[value='"+selValue+"']").text();

            // console.log(selValue);

            if( selValue > 0){
                selectedOptionsNum++;
                $(value).parent().removeClass('error-select');
                saveSelection(selectId, selValue, selectedText);
                if(OptionsNum > 1){
                    stringBreadCrumb += '::'+ selectedText;
                } else {
                    stringBreadCrumb += selectedText;
                }

            } else if(selValue == -1){
                selectedOptionsNum++;
            } else {
                // showMessage("Eroare "+selectedText,'danger');
                $(value).parent().addClass('error-select');
                if($('#scanare_agent:visible')){
                    $('#scanare_agent:visible').addClass('agent-negasit');
                    $('#scanare_agent:visible').attr('placeholder','Agent invalid !');

                }
                // document.getElementById('error_sound').play();

            }

            // console.log(selectId + " " + selValue + " " + selectedText);
        });

        if(OptionsNum > selectedOptionsNum){
            //console.log('eroare');
            return;
        }


        localStorage.setItem('start_scan',1);

        showScanForm();


        lastLevel++;

        breadcrumb_arr[lastLevel] = stringBreadCrumb;
        breadcrumbChange();


        localStorage.setItem('lastLevel',lastLevel);

        if(selects.length > 0){
            changeSelectScan();
        }
        breadcrumbChange();
    });

    $('.app-menu  a.stop-scan').on( "click", function(event) {
        event.preventDefault();
        // $('ul.app-menu .footer-app-acction a').css('display','block');
        $('#scanare_agent').attr('placeholder','SCANARE AGENT');
        $('#scanare_agent').removeClass('agent-gasit');
        $('#scanare_agent').removeClass('agent-negasit');
        if(!salveazaBorderou()){
            return;
        }

        localStorage.removeItem('savedSelections');

        $(this).hide();



        $('#rem-checkbox').attr('checked', false);
        $('#deteriorate-checkbox').attr('checked', false);

        $('.report-codes').html('');

        localStorage.setItem('start_scan',0);

        // sterge coduri din local storage
        $('#barcodes').html("");
        localStorage.removeItem('curent_barcodes');
        totalPuisori = 0;
        coduri_rem = 0;
        barcodesArray = [];
        totalCoduri = 0;
        curentBarcodes = 0;
        coduri_deteriorate = 0;
        $('.report-codes').html('');

        if(lastLevel > 0){
            lastLevel--;
        }

        breadcrumb_arr[lastLevel + 1] = null;
        localStorage.setItem('lastLevel',lastLevel);

        breadcrumbChange();

        restoreSession();

    });


    $('.app-menu  .addCode').on( "click", function(event) {
        event.preventDefault();
        if($(this).hasClass('remove')){
            $(this).html('<span class="glyphicon glyphicon-plus" aria-hidden="true"></span>');
            $(this).removeClass('remove');
        } else {
            $(this).html('<span class="glyphicon glyphicon-minus" aria-hidden="true"></span>');
            $(this).addClass('remove');
        }
        $('#barcode_input').focus();
    });


    $('#scanare_agent').keypress(function(event) {

        $(this).removeClass('agent-gasit');
        $(this).removeClass('agent-negasit');


        if(event.keyCode != 13){
            scanare_agent_key += event.key;
        }
        var d = new Date();
        var n = d.getMilliseconds();

        if(scanare_agent_key_time == 0)
            scanare_agent_key_time = d.getTime();

        var old_time = scanare_agent_key_time;
        scanare_agent_key_time = d.getTime();

        var diff_key = scanare_agent_key_time - old_time;
        scanare_agent_key_total += diff_key;

        var medie  = (scanare_agent_key_total / scanare_agent_key.length);


        if(event.keyCode == 13) {

            if(medie > 250){
                $('#scan_message').html('Introducere invalida!');
                scanare_agent_key = "";
                scanare_agent_key_total = 0;
                scanare_agent_key_time = 0;
                return;
            }


            $('#scan_message').html('');
            var agentID = 0;
            var agentNume = "";
            if(coduri_agenti[scanare_agent_key]){
                agentID = coduri_agenti[scanare_agent_key].id;
                agentNume = coduri_agenti[scanare_agent_key].nume;
            }

            if(agentID > 0){
                $('#select_1_1_1:visible').val(agentID);
                $('#select_1_2_4:visible').val(agentID);
                $('#select_3_9_14:visible').val(agentID);
                $(this).parent().parent().find('li:visible select').selectpicker('refresh');
                $('#scanare_agent').attr('placeholder',agentNume);
                setTimeout(function(){
                    $('.start-scan').click();
                }, 1000);
                $('#scanare_agent').addClass('agent-gasit');
            } else {
                $('#scanare_agent').attr('placeholder','Agent invalid !');
                $('#scanare_agent').addClass('agent-negasit');
                // $('#scan_message').html('Agent invalid !');
            }
            $('#scanare_agent').val(null);
            scanare_agent_key = "";
            scanare_agent_key_total = 0;
            scanare_agent_key_time = 0;
        }
        // coduri_agenti
    });

    $('#barcode_input').keypress(function(event){

        if(event.keyCode == 13){

            sleep(100);

            var code = $( this).val();

            if(!checkInput(code) || code.length < 4){
                $('#barcode_input').val("");
                $('#deteriorate-checkbox').attr('checked', false);
                return;
            }

            var curentBarcodes = JSON.parse(localStorage.getItem('curent_barcodes')) || [];

            if($('.addCode').hasClass('remove')){
                var newBarcodesList = [];
                barcodesArray = [];
                $.each( curentBarcodes, function( key, value ) {
                    if(value.barcode != code){
                        var newItem = {
                            'barcode': value.barcode,
                            'det': value.det,
                            'rem': value.rem,
                            'time': value.time
                        };
                        newBarcodesList.push(newItem);
                        barcodesArray.push(newItem);

                    }
                });

                localStorage.setItem('curent_barcodes', JSON.stringify(newBarcodesList));
                $('#barcode_input').val("");
                $('#deteriorate-checkbox').attr('checked', false);
                $('.addCode').click();

                populateBarcodes();
                return;
            }

            var d = new Date();
            var n = d.getTime() / 1000;
            var newItem = {
                'barcode': code,
                'det': $('#deteriorate-checkbox').is(':checked'),
                'rem' : $('#rem-checkbox').is(':checked'),
                'time': n
            };


            var codeKey = barcodesArray.indexOf(code);

            if(codeKey == -1) {

                barcodesArray.push(code);
                curentBarcodes.push(newItem);
            } else {
                barcodesArray[codeKey] = code;
                curentBarcodes[codeKey] = newItem;
            }

            localStorage.setItem('curent_barcodes', JSON.stringify(curentBarcodes));

            populateBarcodes();
        }
    });



    $('.app-login .cnp-login').keypress(function(event){
        if(event.keyCode == 13){
            doLogin();
            $(this).val("");
        }
    });

    $('.app-login  a.login-but').on( "click", function(event) {
        event.preventDefault();
        doLogin();
    });


    $('.footer-app-acction  a.iesire-app').on( "click", function(event) {
        event.preventDefault();
        logoutAcction();
        restoreSession();
    });


    $('select').on( "change", function(event) {
        var selectId = $(this).attr('id');
        var selectValue = $(this).val();
        var selectedText = $('#'+selectId+" option[value='"+selectValue+"']").text();
        // customSelectTitle = " / " + selectedText;
        // changeTile();

        saveSelection(selectId, selectValue, selectedText);

        // localStorage.setItem(selectId,selectValue);
    });






    /*
    $('#barcode_input').keyup(function () {
        if (!this.value.match(/[0-9]/)) {
            this.value = this.value.replace(/[^0-9]/g, '');
        }
    }); */
    restoreSession();






    lastLevel = localStorage.getItem('lastLevel');

    startLoop();

    $('.app-menu  a.trimite-recantarire').on( "click", function(event) {
        event.preventDefault();

        var greutateVol = false;

        if($('#reweight_lungime').val()){
            greutateVol = true;
        }
        if($('#reweight_latime').val()){
            greutateVol = true;
        }
        if($('#reweight_inaltime').val()){
            greutateVol = true;
        }

        var greutateVolError = false;
        if(greutateVol){
            if(!$('#reweight_lungime').val() ||  !$('#reweight_latime').val() || !$('#reweight_inaltime').val()){
                greutateVolError = true;
            }
        }

        $('#reweight_lungime').removeClass('error-class');
        $('#reweight_latime').removeClass('error-class');
        $('#reweight_inaltime').removeClass('error-class');

        if(greutateVolError){
            if(!$('#reweight_lungime').val()){
                $('#reweight_lungime').addClass('error-class');
            }
            if(!$('#reweight_latime').val()){
                $('#reweight_latime').addClass('error-class');
            }
            if(!$('#reweight_inaltime').val()){
                $('#reweight_inaltime').addClass('error-class');
            }
            return;
        }



        if(!$('#reweight_cod').val()){
            $('#reweight_cod').addClass('error-class');
            return ;
        } else {
            $('#reweight_cod').removeClass('error-class');
        }
        if(!$('#reweight_kg').val()){
            $('#reweight_kg').addClass('error-class');
            return ;
        } else {
            $('#reweight_kg').removeClass('error-class');
        }
        
        var dataScan = 
        
        $.ajax(
            {
                type : 'post',
                url : ajaxUrl,
                dataType : 'json', // expected returned data format.
                data: {
                    userId              : userId,
                    centruId            : centruId,
                    userName            : userName,
                    dataScan            : dataScan,
                    reweight_cod        : $('#reweight_cod').val(),
                    reweight_kg         : $('#reweight_kg').val(),
                    reweight_lungime    : $('#reweight_lungime').val(),
                    reweight_latime     : $('#reweight_latime').val(),
                    reweight_inaltime   : $('#reweight_inaltime').val(),
                },
                success : function(data)
                {
                    if(data.success){
                        $('#reweight_cod').val('');
                        $('#reweight_kg').val('');
                        $('#reweight_lungime').val('');
                        $('#reweight_latime').val('');
                        $('#reweight_inaltime').val('');

                        showMessage("Trimis cu success !",'success')
                    } else {
                        showMessage("Erroare !",'danger')
                    }



                },
                complete : function(data)
                {
                    // isOnLine
                },

            }).done(function() {
                setIsOnLine(true);
            }).fail(function() {
                showMessage("Erroare !",'danger')
                setIsOnLine(false);
            });
    });





    $('.deteriorat-check').on( "click", function(event) {
        $('#barcode_input').focus();
    });

    $('.rem-check').on( "click", function(event) {
        $('#barcode_input').focus();
    });



    // rem-checkbox
   //deteriorate-checkbox


    $('.container').fadeIn('slow');

    if(scan_agent){
        // $('.footer-app-acction .start-scan').addClass('hide-position');
    }



    //showMessage('test','danger');
    //showMessage('test','success');
    //showMessage('test','info');
    //showMessage('test','warning');

});


function startLogoutLoop() {
    if(myIntervalLogout > 0) clearInterval(myIntervalLogout);  // stop
    if(myIntervalLogout > 0) {
        window.clearInterval(myIntervalLogout);
    }
    myIntervalLogout = setInterval( "logoutCheck()", iFrequencyLogout );  // run
}

// STARTS and Resets the loop if any
function startLoop() {
    if(myInterval > 0) clearInterval(myInterval);  // stop
    if(myInterval > 0) {
        window.clearInterval(myInterval);
    }
    myInterval = setInterval( "trimiteBorderouri()", iFrequency );  // run
}


function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}

function changeTile() {
    return ;
}

function afisareMesajBorderouri() {
    var borderouri = JSON.parse(localStorage.getItem('borderouri')) || [];
    var mesaj = 'borderouri de trimis';
    var culoare = 'green';
    var trimite = '<a href="#" onclick="javascript:trimiteBorderouri();return false;">Trimite</a>';

    if(borderouri.length == 1){
        mesaj = 'borderou de trimis';
        culoare = '#f4a941';
    } else if(borderouri.length > 1){
        culoare = '#f44f41';
    } else {
        trimite = '';
    }

    $('#mesaj_borderouri').html("<p style=\"color:"+culoare+";\"><strong>"+borderouri.length + "</strong> " + mesaj + " " + trimite);
}

function logoutCheck() {

    var now = Date.now();
    if(lastActivityTime == 0){
        lastActivityTime = now;
    }
    var lastDiff = Math.round((Date.now() - lastActivityTime) / 1000);
    logOuIn = logoutAfter -lastDiff;

    afisareMesajBorderouri();

    $('#mesaj_delogare').html('Delogare in ' + roundNumber((logOuIn / 60),1) + ' minute ');
    if(lastDiff > logoutAfter){
        $('.stop-scan').addClass('logout_after');
        $('.stop-scan').click();
        trimiteBorderouri();
        logoutAcction();
    }

}

function setCurrentPath(menu_path) {
    localStorage.setItem('menu_path', menu_path);
    changeTile();
}

function restoreSession() {

    userId   = localStorage.getItem('userId');
    centruId = localStorage.getItem('centruId');
    userName = localStorage.getItem('userName');

    if(auto_login.length > 0){
        $('.app-login .cnp-login').hide();
        $('.app-login .cnp-login').val(auto_login);
        doLogin();
        return;
    } else {
        $('.app-login .cnp-login').show();
    }
    $('#barcodes').html('');
    //console.log('userId:'+userId);

    if(userId > 0){

        $('.navbar').show();

        $('.app-menu').show();

        $('.username-text').text(userName);



        //if(agentiCentru.length() == 0){
        agentiCentru = JSON.parse(localStorage.getItem('agentiCentru')) || [];
        //}
        // console.log(agentiCentru);
        var select_1_1_1_selected = getSelectionValue("select_1_1_1");
        $('#select_1_1_1').html('');
        $('#select_1_1_1').append('<option value="0">ALEGE CURIER</option>');

        var select_1_2_4_selected = getSelectionValue("select_1_2_4");
        $('#select_1_2_4').html('');
        $('#select_1_2_4').append('<option value="0">ALEGE CURIER</option>');



        var select_3_9_14_selected = getSelectionValue("select_3_9_14");
        $('#select_3_9_14').html('');
        $('#select_3_9_14').append('<option value="0">ALEGE CURIER</option>');




        $.each( agentiCentru, function( key, value ) {

            var selectedVal = '';
            if(select_1_1_1_selected == value.id){
                selectedVal = ' selected';
                //console.log(value);
            }
            var optionNew = '<option value="'+value.id+'" '+selectedVal+' ruta="">'+ value.denumire +'</option>';
            if(!isBlank(value.denumire)) {
                $('#select_1_1_1').append(optionNew);
            }

            var selectedVal2 = '';
            if(select_1_2_4_selected == value.id){
                selectedVal2 = ' selected';
            }
            var optionNew2 = '<option value="'+value.id+'" '+selectedVal2+' >'+ value.denumire +'</option>';
            if(!isBlank(value.denumire)) {
                $('#select_1_2_4').append(optionNew2);
            }


            var selectedVal3 = '';
            if(select_3_9_14_selected == value.id){
                selectedVal3 = ' selected';
            }
            var optionNew3 = '<option value="'+value.id+'" '+selectedVal3+' >'+ value.denumire +'</option>';
            if(!isBlank(value.denumire)) {
                $('#select_3_9_14').append(optionNew3);
            }


        });

        sortSelect('select_1_1_1');
        sortSelect('select_1_2_4');
        sortSelect('select_3_9_14');

        //if(ruteCentru.length() == 0) {
        ruteCentru = JSON.parse(localStorage.getItem('ruteCentru')) || [];
        //}

        var select_2_3_7_selected = getSelectionValue("select_2_3_7");
        $('#select_2_3_7').html('');
        $('#select_2_3_7').append('<option value="0">Alege ruta</option>');

        var select_2_4_9_selected = getSelectionValue("select_2_4_9");
        $('#select_2_4_9').html('');
        $('#select_2_4_9').append('<option value="0">Alege ruta</option>');

        $.each( ruteCentru, function( key, value ) {
            var selectedVal = '';

            if(select_2_3_7_selected == value.id){
                selectedVal = ' selected';
            }
            var optionNew = '<option value="'+value.id+'" '+selectedVal+' >'+ value.denumire +'</option>';
            if(!isBlank(value.denumire)) {
                $('#select_2_3_7').append(optionNew);
            }

            var selectedVal2 = '';
            if(select_2_4_9_selected == value.id){
                selectedVal2 = ' selected';
            }
            var optionNew2 = '<option value="'+value.id+'" '+selectedVal2+' >'+ value.denumire +'</option>';

            if(!isBlank(value.denumire)){
                $('#select_2_4_9').append(optionNew2);
            }


        });

        sortSelect('select_2_3_7');
        sortSelect('select_2_4_9');

        $('#select_3_9_15').val(0);

        $('.selectpicker').selectpicker({
            style: 'btn widecustom move-to-left',
            size: 12
        });

        $('.selectpicker').selectpicker('refresh');
    } else {
        logoutAcction();
        return;
    }

    var menu_path = localStorage.getItem('menu_path');

    //console.log('menu_path:' + menu_path);

    if(menu_path != 'null' && menu_path){
        $('ul.app-menu > li').addClass('hide-menu');
        $('.app-menu .sub-path-' + menu_path).removeClass('hide-menu');
        $('.footer-app-acction a.prev-page').css('display','block');
        // console.log("menu_path:"+localStorage.getItem('menu_path'));
    }
    $('.footer-app-acction').removeClass('hide-menu');
    changeTile();
    // console.log( 'prev_path:' + localStorage.getItem('prev_path'));

    if($('.next_scan').is(':visible'))
    {
        $('.start-scan').css('display','block');
    }




    populateBarcodes();


    if(localStorage.getItem('start_scan') == 1){
        showScanForm();
    }


    breadcrumb_arr = JSON.parse(localStorage.getItem('breadcrumb_new')) || []; // , JSON.stringify(breadcrumb));

    breadcrumbChange();

    $('.selectpicker').selectpicker({
        style: 'btn widecustom move-to-left',
        size: 12
    });


    switch(menu_path) {


        case '1_1':
            if(scan_agent && localStorage.getItem('start_scan') != 1){
                $('.path-1_1_1').addClass('hide-position');
                $('.scanare-agent').css('display','block');
                $('#scanare_agent').focus();
            }
            break;
        case '1_2':
            if(scan_agent && localStorage.getItem('start_scan') != 1){
                $('.path-1_2_4').addClass('hide-position');
                $('.scanare-agent').css('display','block');
                $('#scanare_agent').focus();
            }
            break;
        case '2_5':

            break;
        case '2_6':

            break;
        case '2_7':

            break;
        case '2_8':
            $('.app-reweight-area').toggleClass('hide-menu');
            $('#reweight_cod').focus();
            $('.start-scan').css('display','none');
            break;

        case '3':
            if(scan_agent && localStorage.getItem('start_scan') != 1){
                $('.path-3_9_14').addClass('hide-position');
                $('.scanare-agent').css('display','block');
                $('#scanare_agent').focus();
            }
            $('.start-scan').css('display','block');
            $('.path-3_9_14').removeClass('hide-menu');
            $('.path-3_9_15').removeClass('hide-menu');

            /*if(localStorage.getItem('start_scan') == 1){
             $('.level-2').hide();
             $('.start-scan').hide();
             }
             */
            break;

        default:

    }
    // console.log(menu_path);
}


function populateBarcodes() {

    lastActivityTime = Date.now();

    barcodesArray = [];

    $('#barcodes').html('');
    var curentBarcodes = JSON.parse(localStorage.getItem('curent_barcodes')) || [];

    curentBarcodes.reverse();

    $.each( curentBarcodes, function( key, value ) {
        if(!value){
            return;
        }
        barcodesArray.push(value.barcode);
        var awb_extra_class = 'cod_type_';
        if(value.det){
            awb_extra_class += 'det';
            coduri_deteriorate++;
        }
        if(value.rem){
            awb_extra_class += 'rem';
            coduri_rem++;
        }

        var newtime = value.time * 1000;

        var date = new Date(parseFloat(newtime));

        $('#barcodes').append('<p class="awb_'+value.barcode+' '+awb_extra_class+'">' + value.barcode + '<span>'+pad(date.getHours())+':'+pad(date.getMinutes())+':'+pad(date.getSeconds())+'</span> </p>');

    });

    $('#barcode_input').val("");
    var objDiv = $('#barcodes');
    if (objDiv.length > 0){
        objDiv[0].scrollTop = 0 ; // objDiv[0].scrollHeight;
    }

    $('#deteriorate-checkbox').attr('checked', false);

    updateScanReport();
}


function updateScanReport() {

    totalPuisori = 0;
    totalCoduri = 0;
    coduri_deteriorate = 0;
    coduri_rem = 0;


    var curentBarcodes = JSON.parse(localStorage.getItem('curent_barcodes')) || [];
    $.each( curentBarcodes, function( key, value ) {
        if(!value){
            return;
        }
        barcode = value.barcode;
        totalCoduri++;
        if(!barcode){
            return;
        }
        if(barcode.split('-').length > 1){
            totalPuisori++;
        }

        if(value.rem){
            coduri_rem++;
        }
        if(value.det){
            coduri_deteriorate++;
        }
    });


    var deteriorateText = "";
    var remText = "";
    if(coduri_deteriorate > 0){
        deteriorateText = ' Det: ('+ coduri_deteriorate + ')';
    }
    if(coduri_rem > 0){
        remText = ' REM: ('+ coduri_rem + ')';
    }

    $('.report-codes').html('NT: (' + (totalCoduri - totalPuisori) + ') Puisori: (' + totalPuisori + ') '+ deteriorateText + remText +' Total piese: (' + totalCoduri +')');
}

function logoutAcction() {

    localStorage.removeItem('menu_path');
    localStorage.removeItem('prev_path');
    localStorage.removeItem('checkpoints');
    localStorage.removeItem('agentiCentru');
    localStorage.removeItem('ruteCentru');
    localStorage.removeItem('userId');
    localStorage.removeItem('centruId');
    localStorage.removeItem('userName');
    localStorage.removeItem('savedSelections');
    localStorage.removeItem('breadcrumb_new');


    $('.app-area .messages').text('');
    $('.app-login').removeClass('hideLogin');
    $('.cnp-login').focus();
    $('.app-menu').hide();
    $('.navbar').hide();
}


function showScanForm() {
    $('ul.app-menu > li').addClass('hide-menu');
    $('ul.app-menu .app-scan-area').removeClass('hide-menu');
    $('ul.app-menu .footer-app-acction').removeClass('hide-menu');
    $('ul.app-menu .footer-app-acction a').hide();
    $('ul.app-menu .footer-app-acction a.stop-scan').css('display','block');
    $('#barcode_input').focus();
    $('.deteriorat-check').hide();
    $('.rem-check').hide();

    var menu_path = localStorage.getItem('menu_path');
    $('#scan_select').html('');
    if(scan_agent){
        $('.scanare-agent').hide();
    }
    switch(menu_path) {
        case '1_1':
            $('.rem-check').show();
            $('.deteriorat-check').hide();
            // $('#scan_select').append('<option value="0">Alege checkpoint</option>');
            // $('#scan_select').append('<option value="1">Intrare Curier</option>');
            // $('#scan_select').append('<option value="2">Retur la magazie</option>');
            break;
        case '1_2':
            $('.deteriorat-check').hide();
            //$('.rem-check').show();
            break;
        case '2_3':
            $('.deteriorat-check').show();
            break;
        case '2_4':
            $('.deteriorat-check').hide();
            break;
        case '2_8':
            // console.log('isRW');
            $('.deteriorat-check').show();
            $('#reweight_cod').focus();
            $('.start-scan').css('display','none');

            break;


        default:

    }





    //  console.log(menu_path);

}


function pad(d) {
    return (d < 10) ? '0' + d.toString() : d.toString();
}


function breadcrumbChange() {
    $('.breadcrumb').html('');
    var numBreadcrumb = 0;
    $('.breadcrumb').append('<li>Acasa</li>');
    var body_class = 'app';
    $.each( breadcrumb_arr, function( key, value ) {
        if(value != null && value != 'null'){
            $('.breadcrumb').append('<li>'+value+'</li>');
            if(numBreadcrumb < 2){
                body_class += '_'+value.toLowerCase();
            }

            numBreadcrumb++;
        }
    });
    if(numBreadcrumb == 0){
        $('.iesire-app').show();
    } else {
        $('.iesire-app').hide();
    }

    $('body').removeAttr('class');
    $('body').addClass(body_class.replace(' ','_'));

    /*
     if(breadcrumb[1] == 'Curier'){
     $('body').css('background','#ffcc00');
     } else if (breadcrumb[1] == 'Centru'){
     $('body').css('background','#0080ff');
     } else {
     $('body').css('background','transparent');
     }
     */
    localStorage.setItem('breadcrumb_new', JSON.stringify(breadcrumb_arr));

}


function trimiteBorderouri() {


    // changeBarcode();

    if(sendInProgress === true){
        return ;
    }

    sendInProgress = true;
    var borderouri = JSON.parse(localStorage.getItem('borderouri')) || [];

    if(borderouri.length == 0){
        sendInProgress  = false;
        logoutAppCheck();
        return;
    }


    $.post(ajaxUrl, { borderouri: JSON.stringify(borderouri) } , function (data) {

        var dataJson = JSON.parse(data);

        var borderouri = JSON.parse(localStorage.getItem('borderouri')) || [];

        $.each( dataJson, function( key, value ) {
            var bHasError = false;
            $.each( value, function( tip, resp ) {

                if(resp.error){
                    showMessage('<b>' +tip + '</b> ' + resp.error,'danger');
                    bHasError = true;
                    return;
                }
                var res = JSON.parse(resp.response);
                // console.log(resp);

                if(res.error != '0'){
                    //console.log('has error');
                    if(res.message){
                        showMessage('<b>' +tip + '</b> ' + res.message,'danger');
                        bHasError = true;
                        return;
                    }
                } else {
                    showMessage('<b>' +tip + '</b> ' + res.message,'success');
                }

            });

            if(value.error){
                showMessage(value.error,'danger');
            } else {
                // borderouri[key] = null;
            }

            if(bHasError === false){
                borderouri[key] = null;
            }


        });

        var borderouriNoi = [];
        $.each( borderouri, function( key, value ) {
            if(value != null){
                borderouriNoi.push(value);
            }
        });

        // console.log(borderouriNoi.length);

        localStorage.setItem('borderouri', JSON.stringify(borderouriNoi));


        logoutAppCheck();

        sendInProgress = false;

    }).done(function() {
        setIsOnLine(true);
        sendInProgress = false;
    }).fail(function() {
        setIsOnLine(false);
    });
}


function logoutAppCheck() {
    if($('.app-menu  a.stop-scan').hasClass('logout_after')){
        // console.log("astart");
        logoutAcction();
        setTimeout(function(){
            window.location = HTTP + "delogare";
        }, 4000);
    }
}

function setIsOnLine(val) {
    isOnLine = val;
    if(val){
        $('.container').removeClass('no-connection');
    } else {
        $('.container').addClass('no-connection');
    }
}

function salveazaBorderou() {

    var borderouri = JSON.parse(localStorage.getItem('borderouri')) || [];
    var curent_barcodes = JSON.parse(localStorage.getItem('curent_barcodes')) || [];
    var savedSelections = JSON.parse(localStorage.getItem('savedSelections')) || [];
    if(curent_barcodes.length == 0 || userId == 0){
        return true;
    }
    var borderouNou = {
        'userId': userId,
        'centruId': centruId,
        'userName' : userName,
        'menu_path' : localStorage.getItem('menu_path'),
        'savedSelections' : savedSelections,
        'coduri': curent_barcodes,
        'time': Date.now()
    };

    borderouri.push(borderouNou);
    // console.log(borderouri);
    localStorage.setItem('borderouri', JSON.stringify(borderouri));
    return true;
}

function changeSelectScan() {

    var savedSelections = JSON.parse(localStorage.getItem('savedSelections'));

    $.each( savedSelections, function( key, value ) {
        // console.log('changeSelectScan');
        //  console.log(value.id +" "+value.value);
    });

}

function doLogin() {
    var cnp = $('.app-login .cnp-login').val();

    if(cnp.length < 3){
        return;
    }


    $('body').css('background-color','#ecf0f1');
    $('.loader').show();

    $('.app-login').toggleClass('hideLogin');

    $.ajax(
        {
            type : 'post',
            url : ajaxUrl,
            dataType : 'json', // expected returned data format.
            data: { cnp: cnp },
            success : function(data)
            {
                if(!data){
                    return;
                }

                if(data.id > 0){

                    userId = data.id;
                    centruId = data.centru;
                    userName = data.user;
                    // $('.app-area .messages').text(userId + " " + centruId + " " + userName);
                    //$('.app-login').toggleClass('hideLogin');

                    localStorage.setItem('userId',userId);
                    localStorage.setItem('centruId',centruId);
                    localStorage.setItem('userName',userName);

                    localStorage.setItem('agentiCentru',JSON.stringify(data.agenti));
                    localStorage.setItem('ruteCentru',JSON.stringify(data.rute));
                    localStorage.setItem('checkpoints',JSON.stringify(data.checkpoints));

                    agentiCentru    = data.agenti;
                    ruteCentru      = data.rute;
                    checkpoints     = data.checkpoints;

                    //restoreSession();

                    //$('body').css('background-color','transparent');
                    $('.app-area .messages').html('');
                    location.reload();
                    $('.app-area .messages').html('');

                } else {
                    $('.app-login .cnp-login').show();
                    $('.app-login').toggleClass('hideLogin');
                    //$('.app-area .messages').html('<span class="error">Utilizator inexistent ('+cnp+')</span>');
                }
                /* var result1, result2, message;

                 for(var i = 0; i < data.length; i++)
                 {

                 result1 = data[i].result1;
                 result2 = data[i].result2;
                 message = data[i].message;
                 } */
            },
            complete : function(data,aaaa)
            {
                // do something, not critical.

                //console.log('userId::::' +userId);
                if(userId == 0 || userId == null){
                    var json = {};
                    if(data.responseText){
                        json = JSON.parse(data.responseText);
                    }

                    if(json.eroare){
                        showMessage(json.eroare,'danger');
                    } else if (json.success > 0) {
                        showMessage('Utilizator inexistent ('+cnp+')','danger');
                    } else {
                        showMessage('Eroare','danger');
                    }

                    $('.loader').hide();
                    $('body').css('background-color','transparent');
                    $('.app-login').removeClass('hideLogin');
                }

            }
        }).done(function() {
        setIsOnLine(true);
    }).fail(function() {
        setIsOnLine(false);
    });
}

function checkConnexion(){
    $.ajax(
        {
            type : 'post',
            url : ajaxUrl,
            dataType : 'json', // expected returned data format.
            data: { checkConnexion: 1 },
            success : function(data)
            {

            },
            complete : function(data)
            {

            }
        }).done(function() {
        setIsOnLine(true);
    }).fail(function() {
        setIsOnLine(false);
    });
}


function deleteAllCodes(){
    localStorage.setItem('curent_barcodes', null);
    localStorage.removeItem('curent_barcodes');
}


function showMessage(mess , type){
    $.notify({
        message: mess
    },{
        type: type ,
        animate: {
            enter: 'animated fadeInDown',
            exit: 'animated fadeOutUp'
        }
    });
}

function checkInput(ob) {
    var invalidChars = /[^0-9-]/gi
    if(invalidChars.test(ob)) {
        ob = ob.replace(invalidChars,"");
        return false;
    }
    if ($(".awb_" + ob)[0] && !$('.addCode').hasClass('remove')){
        return false;
    }
    return true;
}

function saveSelection(selectId, selectValue, selectText) {

    var oldItems = JSON.parse(localStorage.getItem('savedSelections')) || [];

    var fundVal = false;

    var newItem = {
        'id': selectId,
        'value': selectValue,
        'text' : selectText,
        'level': lastLevel
    };

    $.each( oldItems, function( key, value ) {
        if(value.id == selectId){
            oldItems[key] = newItem;
            fundVal = true;
        }
    });

    if(fundVal === false){
        oldItems.push(newItem);
    }
    // console.log(oldItems);
    localStorage.setItem('savedSelections', JSON.stringify(oldItems));
}

function getSelectionValue(selectId){
    var oldItems = JSON.parse(localStorage.getItem('savedSelections')) || [];
    var returnVal  = 0;
    $.each( oldItems, function( key, value ) {
        if(value.id == selectId){
            returnVal = value.value;
        }
    });
    return returnVal;
}



function randomIntFromInterval(min,max)
{
    return Math.floor(Math.random()*(max-min+1)+min);
}
function changeBarcode() {
    document.getElementById("barcode_gen").src="barcode.php?text=" + randomIntFromInterval(4444444,9999999);
    document.getElementById("barcode_gen_pui").src="barcode.php?text=" + randomIntFromInterval(4444444,9999999) + "-" +randomIntFromInterval(111,222);
}



function sortSelect(selElem) {
    selElem = document.getElementById(selElem);

    var text = selElem.options[0].text;
    var val  = selElem.options[0].value;

    var tmpAry = new Array();
    for (var i=0;i<selElem.options.length;i++) {
        if(selElem.options[i].value > 0){
            tmpAry[i] = new Array();
            tmpAry[i][0] = selElem.options[i].text;
            tmpAry[i][1] = selElem.options[i].value;
        }

    }
    tmpAry.sort();
    while (selElem.options.length > 0) {
        selElem.options[0] = null;
    }

    var replaced = null;
    for (var i=0;i<tmpAry.length;i++) {
        if(tmpAry[i]){
            var op = new Option(tmpAry[i][0], tmpAry[i][1]);
            selElem.options[i] = op;
        }


    }
    if(text){
        $('#' + selElem.getAttribute('id')).prepend('<option selected value="'+val+'">'+text+'</option>');
    }
    return;
}


function isBlank(str) {
    return (!str || /^\s*$/.test(str));
}



function sleep(miliseconds) {
    var currentTime = new Date().getTime();
    while (currentTime + miliseconds >= new Date().getTime()) {
    }
}
/*
var oldItems = JSON.parse(localStorage.getItem('menuTree')) || [];

var newItem = {
    'current': itemContainer.find('h2.product-name a').text(),
    'product-image': itemContainer.find('div.product-image img').attr('src'),
    'product-price': itemContainer.find('span.product-price').text()
};

oldItems.push(newItem);

localStorage.setItem('menuTree', JSON.stringify(oldItems));

 logoutAfter

*/
