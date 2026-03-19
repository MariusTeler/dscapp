/**
 * Created by marian on 10/07/2019.
 */

var decont_step = 0;

$(document).ready(function()
{
    $('.content').removeClass('ui-widget-content');
    Grid_DecontIncasateCash();
    Grid_DecontNeincasate();
    Grid_DecontIncasateCard();
    Grid_DecontNelivrate();
    Grid_DecontCheltuieli();

    $('.selectpicker').selectpicker({
        style: 'btn-warning',
        size: 15
    });

    $('.datepicker').datepicker({
        format: 'dd.mm.yyyy'
    });

    $('#data_export').on('changeDate', function(ev){
        $(this).datepicker('hide');
    });
    UpdateDecontDash();

    $('.info-dash').on( "dblclick", function(event) {
        UpdateDecontDash();
    });

    $('#agent_totaluri').on( "dblclick", function(event) {
        $('#agent_totaluri').html('');
        getAgentTotal();
    });
    $('#decont_id').val(0);

    $('#next_btn').on( "click", function(event) {
        event.preventDefault();
        decont_step++;
        switch (decont_step){
            case 1:
                $('.step_1').show();
                $('.step_2').hide();
                $('.step_3').hide();
                $('#next_btn').html('Continua <span class="glyphicon glyphicon-chevron-right"></span>');
                break;
            case 2:
                $('.step_1').hide();
                $('.step_2').show();
                $('.step_3').hide();
                $("#tabel_cheltuieli").jqGrid('setGridParam', { url: '/decont_card/json/tabel_cheltuieli/' + $('#agent_decont').val()}).trigger("reloadGrid");
                $('#next_btn').html('Continua <span class="glyphicon glyphicon-chevron-right"></span>');
                break;
            case 3:
                $('.step_1').hide();
                $('.step_2').hide();
                $('.step_3').show();
                $('#next_btn').html('Incasat <span class="glyphicon glyphicon-ok"></span>');
                $('#pv_btn').hide();
                $('#first_page_btn').hide();
                getAgentTotal();
                break;
            case 4:
                inchideDecontAgent();
                decont_step = 0;
                break;
            default:
                $('#next_btn').hide();
                $('#back_btn').hide();
                $('.step_1').hide();
                $('.step_2').hide();
                $('.step_3').hide();
                $('#pv_btn').hide();
                $('#first_page_btn').hide();
                decont_step = 0;
                break;
        }

    });

    $('#first_page_btn').on( "click", function(event) {
        event.preventDefault();
        $('#agent_totaluri').html('');
        $('#decont_id').val(0);
        decont_step = 0;
        backAcction();
        UpdateDecontDash();
    });

    $('#pv_btn').on( "click", function(event) {
        event.preventDefault();
        var decont_id = parseInt($('#decont_id').val());
	    if(isNaN(decont_id) || decont_id == 0) { AfiseazaEroareDecont('Incaseaza decontul mai intii'); return false; }
        printPVAgent(decont_id);
    });

    $('#agent_decont').change(function(){

        $('.dashboard-acction').hide();
        $('.info-dash').hide();
        $('.inchidere-zi-decont').removeAttr('disabled');

        $('.agent-decont-select .dropdown-toggle').removeClass('btn-warning');
        if($('#agent_decont').val() == 0){
            $('.agent-decont-select .dropdown-toggle').removeClass('btn-default');
            $('.agent-decont-select .dropdown-toggle').addClass('btn-danger');
            decont_step = 0;
            $('#next_btn').hide();
            $('#pv_btn').hide();
            $('#first_page_btn').hide();
        } else {
            decont_step = 1;
            $('#next_btn').show();
            $('.step_1').show();

            $('.agent-decont-select .bootstrap-select').hide();
            $('#agent_decont_selectat').val($("#agent_decont option:selected").text());
            $('#agent_decont_selectat').show();
            $('#back_btn').show();

            $('.agent-decont-select .dropdown-toggle').removeClass('btn-danger');
            $('.agent-decont-select .dropdown-toggle').addClass('btn-default');

            $("#tabel_incasate_cash").jqGrid('setGridParam', { url: '/decont_card/json/tabel_incasate_cash/' + $('#agent_decont').val()}).trigger("reloadGrid");
            $("#tabel_incasate_card").jqGrid('setGridParam', { url: '/decont_card/json/tabel_incasate_card/' + $('#agent_decont').val()}).trigger("reloadGrid");
            $("#tabel_neincasate").jqGrid('setGridParam', { url: '/decont_card/json/tabel_neincasate/' + $('#agent_decont').val()}).trigger("reloadGrid");
            $("#tabel_nelivrate").jqGrid('setGridParam', { url: '/decont_card/json/tabel_nelivrate/' + $('#agent_decont').val()}).trigger("reloadGrid");
        }
    });

    $("#back_btn").click(function(){
        $(".btn, .form-control").removeClass('btn-danger');
        backAcction();
    });

    $("#agent_decont_selectat").dblclick(function(){
        backAcction();
    });

    $('.step_1, .step_2, .step_3').hide();
    $('.step_1, .step_2, .step_3').css('visibility','visible');
    $('#pv_btn').hide();
    $('#first_page_btn').hide();
    Grid_CurieriDeDecontat();
});


function backAcction() {

    if(decont_step>0)
        decont_step--;


    $('#next_btn').html('Continua <span class="glyphicon glyphicon-chevron-right"></span>');
    switch (decont_step){
        case 0:
            $('#back_btn').hide();
            $('#next_btn').hide();
            $('#pv_btn').hide();
            $('#first_page_btn').hide();
            $('.step_1').hide();
            $('.dashboard-acction').show();
            $('.info-dash').show();

            $('#agent_decont_selectat').val('');
            $('#agent_decont_selectat').hide();
            $('.agent-decont-select .bootstrap-select').show();
            $('#agent_decont').val(0);

            $("#tabel_incasate_cash").jqGrid("clearGridData");
            $("#tabel_incasate_cash").jqGrid("getGridParam").search = false;
            $("#tabel_incasate_cash").triggerHandler("jqGridRefreshFilterValues");     
            $("#tabel_incasate_card").jqGrid("clearGridData");
            $("#tabel_incasate_card").jqGrid("getGridParam").search = false;
            $("#tabel_incasate_card").triggerHandler("jqGridRefreshFilterValues"); 
            $("#tabel_neincasate").jqGrid("clearGridData");
            $("#tabel_nelivrate").jqGrid("clearGridData");
            $("#tabel_cheltuieli").jqGrid("clearGridData");

            $('.selectpicker').selectpicker('refresh');
            break;

        case 1:
            $('#next_btn').show();
            $('#pv_btn').hide();
            $('#first_page_btn').hide();
            $('.step_1').show();
            $('.step_2').hide();
            break;
        case 2:
            $('#next_btn').show();
            $('#pv_btn').hide();
            $('#first_page_btn').hide();
            $('.step_1').hide();
            $('.step_2').show();
            $('.step_3').hide();
            break;
        }
}


function AfiseazaEroareDecont(mesaj, title = 'Error', type = 'type-error') {
    var modal = $('#error');
    modal.find('.modal-title').text(title);
    modal.find('.modal-body').text(mesaj);
    modal.modal('show');
}

var allowKeys = [37, 38,39, 40, 45, 46, 97, 99, 118, 120, 121, 122];  // Accept ctrl+v, ctrl+c, ctrl+z, ctrl+insert, shift+insert, shift+del and ctrl+a

function validateInt(evt) {
    var theEvent = evt || window.event;
    var key = theEvent.key || theEvent.which || theEvent.keyCode;

    if( ['Backspace', 'Tab', 'Enter', 'Shift', 'Control', 'Insert', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(key) || allowKeys.indexOf(theEvent.which) != -1 || allowKeys.indexOf(theEvent.keyCode) != -1)
        return true;

    var regex = /[0-9]|\./;
    if( !regex.test(key) ) {
        theEvent.returnValue = false;
        if(theEvent.preventDefault) theEvent.preventDefault();
    }
}

function Grid_DecontIncasateCash(){
    var grid = $('#tabel_incasate_cash');
    grid.jqGrid({
        url : '/decont_card/json/tabel_incasate_cash/' + $('#agent_decont').val(),
        datatype : "json",
        colModel : [ {
            label : 'Fct./Chit.',
            name : 'fctChit',
            search: false,
            sortable:false,
            align: 'left',
        },{
            label : 'Valoare',
            name : 'valoare',
            search: false,
            sortable:false,
            align: 'right',
        },{
            label : 'NT/Client',
            name : 'ntsClient',
            search: false,
            sortable:false,
            align: 'right',
        },{
            label : 'Tip',
            name : 'tip',
            index : 'tip',
            sortable : false,
            formatter: 'select',
            edittype:'select',
            editoptions : {
                value : "1:Transport;2:Ramburs;3:Client CTR",
            },
            stype : 'select',
            searchoptions : {
                value : ":Toate;1:Transport;2:Ramburs;3:Client CTR",
                sopt : [ 'eq' ]
            },
            align: 'center'
        },{
            label : 'Operatiune',
            name : 'operatiune',
            index : 'operatiune',
            sortable : false,
            formatter: 'select',
            edittype:'select',
            editoptions : {
                value : "1:Colectare;2:Livrare;3:Client CTR",
            },
            stype : 'select',
            searchoptions : {
                value : ":Toate;1:Colectare;2:Livrare;3:Client CTR",
                sopt : [ 'eq' ]
            },
            align: 'center', 
        }, {
            label : 'Data inc.',
            name : 'dataInc',
            sortable : false,
            search: false,
            sorttype : 'date',
            formatter : 'date',
            formatoptions : {
                srcformat : 'Y-m-d H:i:s',
                newformat : 'd.m.Y H:i'
            },
            align: 'center'
        },{
            label : 'vt',
            name : 'vt',
            hidden: true
        }],
        autowidth : true,
        width : 960,
        scroll : true,
        rowNum : 1000,
        mtype : "GET",
        shrinkToFit: false,
        forceFit: true,
        height:250,
        rownumbers : true,
        viewrecords : true,
        caption : false,
        multiselect : false,
        footerrow: true,
        userDataOnFooter: true,
        gridComplete: function () {
            var rows = grid.getDataIDs();
            for (var i = 0; i < rows.length; i++)
            {
                var vt = grid.getCell(rows[i],"vt");

                if(vt > 0)
                    grid.jqGrid('setCell',rows[i],"fctChit", "", {  'font-weight':'bold', 'background':'#7CFC00'} );
                else
                    grid.jqGrid('setCell',rows[i],"fctChit", "", {  'background':'#FF9497'} );
            }
        }
    })
    .jqGrid('bindKeys')
    .jqGrid('filterToolbar',{
        stringResult : true,
        searchOnEnter : false
    });
}

function Grid_DecontIncasateCard(){
    var grid = $('#tabel_incasate_card');
    grid.jqGrid({
        url : '/decont_card/json/tabel_incasate_card/' + $('#agent_decont').val(),
        datatype : "json",
        colModel : [ {
            label : 'Fct./Chit.',
            name : 'fctChit',
            search: false,
            sortable:false,
            align: 'left',
        },{
            label : 'Valoare',
            name : 'valoare',
            search: false,
            sortable:false,
            align: 'right',
        },{
            label : 'NT/Client',
            name : 'ntsClient',
            search: false,
            sortable:false,
            align: 'right',
        },{
            label : 'Tip',
            name : 'tip',
            index : 'tip',
            sortable : false,
            stype : 'select',
            formatter: 'select',
            edittype : "select",
            editoptions : {
                value : "1:Transport;2:Ramburs;3:Client CTR"
            },
            stype : 'select',
            searchoptions : {
                value : ":Toate;1:Transport;2:Ramburs;3:Client CTR",
                sopt : [ 'eq' ]
            },
            align: 'center'
        },{
            label : 'Operatiune',
            name : 'operatiune',
            index : 'operatiune',
            sortable : false,
            formatter: 'select',
            editoptions : {
                value : "1:Colectare;2:Livrare;3:Client CTR"
            },
            stype : 'select',
            searchoptions : {
                value : ":Toate;1:Colectare;2:Livrare;3:Client CTR",
                sopt : [ 'eq' ]
            },
            align: 'center',
            edittype : "select",
        }, {
            label : 'Data inc.',
            name : 'dataInc',
            sortable : false,
            search: false,
            sorttype : 'date',
            formatter : 'date',
            formatoptions : {
                srcformat : 'Y-m-d H:i:s',
                newformat : 'd.m.Y H:i'
            },
            align: 'center'
        }],
        autowidth : true,
        width : 960,
        scroll : true,
        rowNum : 100,
        mtype : "GET",
        shrinkToFit: false,
        forceFit: true,
        height:100,
        rownumbers : true,
        viewrecords : true,
        caption : false,
        multiselect : false,
        footerrow: true,
        userDataOnFooter: true,
    })
    .jqGrid('bindKeys')
    .jqGrid('filterToolbar',{
        stringResult : true,
        searchOnEnter : false
    });
}

function Grid_DecontNelivrate(){
    var grid = $('#tabel_nelivrate');
    grid.jqGrid({
        url : '/decont_card/json/tabel_nelivrate/' + $('#agent_decont').val(),
        datatype : "json",
        colModel : [{
            label : 'Expeditie',
            name : 'expeditie',
            index : 'ep.expeditie',
            width: 100
        }, {
            label : 'Expeditor',
            name : 'expeditor',
            index : 'cle.nume',
            width: 200
        }, {
            label : 'Destinatar',
            name : 'destinatar',
            index : 'cld.nume',
            width: 200
        }, {
            label : 'Transport',
            name : 'de.transport',
            align: 'right',
            width: 80
        }, {
            label : 'Ramburs',
            name : 'dr.amburs',
            sortable : false,
            align: 'right',
            width: 80,
        }, {
            label : 'Total',
            name : 'total',
            sortable : false,
            align: 'right',
            width: 80
        }, {
            label : 'Ckp',
            name : 'ckp',
            sortable : false,
            align: 'center',
            width: 220
        }],
        autowidth : true,
        width : 960,
        rowNum : 100,
        scroll : true,
        mtype : "GET",
        shrinkToFit: true,
        forceFit: true,
        height:150,
        rownumbers : true,
        viewrecords : true,
        caption : false,
        footerrow: true,
        userDataOnFooter: true,
        ondblClickRow : function(id, row, col) {
            AfisareDetaliiExpeditieModalCuID(id);
        }
    })
    .jqGrid('bindKeys');
    /*.jqGrid('filterToolbar',{
        stringResult : true,
        searchOnEnter : false
    });
    */
}

function Grid_DecontNeincasate(){
    var grid = $('#tabel_neincasate');
    grid.jqGrid({
        url : '/decont_card/json/tabel_neincasate/' + $('#agent_decont').val(),
        datatype : "json",
        colModel : [{
            label : 'Expeditie',
            name : 'expeditie',
            index : 'ep.expeditie',
            width: 60
        }, {
            label : 'Expeditor',
            name : 'expeditor',
            index : 'cle.nume',
            width: 200
        }, {
            label : 'Destinatar',
            name : 'destinatar',
            index : 'cld.nume',
            width: 200
        }, {
            label : 'Platitor',
            name : 'destinatar',
            index : 'clp.nume',
            width: 200
        }, {
            name : 'Mod plata',
            stype : 'select',
            formatter: 'select',
            edittype : "select",
            editoptions : {
                value : ": ;0:Per NT;1:Factura periodica",
            },
            searchoptions : {
                value : ":Toate;0:Per NT;1:Factura periodica",
                sopt : [ 'eq' ]
            },
            sortable : false,
            width: 80
        }, {
            label : 'Transport',
            name : 'transport',
            sortable : false,
            align: 'right',
            width: 60
        }, {
            label : 'Ckp',
            name : 'ckp',
            sortable : false,
            align: 'center',
            width: 220
        }],
        autowidth : true,
        width : 960,
        rowNum : 100,
        scroll : true,
        mtype : "GET",
        shrinkToFit: true,
        forceFit: true,
        height:100,
        rownumbers : true,
        viewrecords : true,
        caption : false,
        footerrow: true,
        userDataOnFooter: true,
        ondblClickRow : function(id, row, col) {
            AfisareDetaliiExpeditieModalCuID(id);
        }
    })
    .jqGrid('bindKeys');
}

function Grid_DecontCheltuieli(){
    var grid = $('#tabel_cheltuieli');
    grid.jqGrid({
        url : '/decont_card/json/tabel_cheltuieli/' + $('#agent_decont').val(),
        datatype : "json",
        colModel : [ {
            label : 'Tip',
            name : 'tip',
            index : 'tip',
            editable : true,
            width: 130,
            formatter: 'select',
            edittype : "select",
            editoptions : {
                value : listaTipCheltuieli
            },
            searchoptions : {
                value : listaTipCheltuieli,
                sopt : [ 'eq' ]
            }
        },{
            label : 'Suma',
            name : 'suma',
            index : 'suma',
            sortable : false,
            align: 'right',
            editable : true,
            width: 130
        }, {
            label : 'descriere',
            name : 'descriere',
            index : 'descriere',
            editable : true,
            width: 400
        } ],
        autowidth : true,
        width : 960,
        rowNum : 100,
        scroll : true,
        mtype : "GET",
        shrinkToFit: false,
        forceFit: true,
        height:350,
        rownumbers : true,
        viewrecords : true,
        caption : false,
        footerrow: true,
        userDataOnFooter: true,
        editurl: '/decont_card/edit_cheltuiala/',
        ondblClickRow : function(rowid, nr, col,  row) {
            grid.jqGrid("editRow", rowid, true, '', '', '', '', reloadCheltuieli);
        }
    })
    .jqGrid('bindKeys');
}

function reloadCheltuieli(rowid, result) {
    $("#tabel_cheltuieli").trigger("reloadGrid");
}

function scanConfirm() {
    $('#scan_coduri_area').val('');
    $('#scan_confirm_popup').modal('show');
}

function scanConfirmPost() {
    $('#scan_confirm_popup').modal('hide');
    var coduri = $('#scan_coduri_area').val();
    var agent_id = parseInt($('#agent_decont').val());
    if(isNaN(agent_id) || agent_id == 0) { AfiseazaEroareDecont('Nu gasesc agentul pentru care se cere validarea !'); return false; }

    if(coduri){
        $.ajax({
            type: "POST",
            url: "/decont_card/scan_validare",
            data: {agent_id : agent_id, coduri : coduri},
            dataType: "json",
            success: function(data) {
                if(data.errorCode > 0) {
                    AfiseazaEroareDecont(data.errorMsg + (data.records.length > 0 ? ("\n" + data.records) : ""));
                }
                $("#tabel_incasate_cash").jqGrid('setGridParam', { url: '/decont_card/json/tabel_incasate_cash/' + $('#agent_decont').val()}).trigger("reloadGrid");
            },
            error: function() {
                AfiseazaEroareDecont("Server is down");
            }
        });
    }
}

function decontGridCulori(elem){
    var iCol = getColumnIndexByName($(elem),'tip'),
        cRows = elem.rows.length, iRow, row, className;

    for (iRow=0; iRow<cRows; iRow++) {
        row = elem.rows[iRow];
        className = row.className;
        if ($.inArray('jqgrow', className.split(' ')) > 0) {
            var tip = $(row.cells[iCol]).html();
            if (tip == 2) {
                if ($.inArray('myAltRowClass', className.split(' ')) === -1) {
                    row.className = className + ' fontRed';
                }
            }
        }
    }
}

function AdaugaCheltuiala(){

    var eroare = false;
    var agent_id = parseInt($('#agent_decont').val());
    let suma = parseFloat($('#suma').val());
    if(isNaN(agent_id) || agent_id == 0) { AfiseazaEroareDecont('Nu gasesc agentul pentru care se cere cheltuiala'); return false; }

    if($('#tip_cheltuiala').val() == 0){
        $('.tip-decont-cheltuiala-select .dropdown-toggle').addClass('btn-danger');
        eroare = true;
    } else {
        $('.tip-decont-cheltuiala-select .dropdown-toggle').removeClass('btn-danger');
    }

    if(isNaN(suma) || suma <= 0){
        $('#suma').addClass('btn-danger');
        eroare = true;
    } else {
        $('#suma').removeClass('btn-danger');
    }

    if($('#descriere').val().length < 3){
        $('#descriere').addClass('btn-danger');
        eroare = true;
    } else {
        $('#descriere').removeClass('btn-danger');
    }

    if(eroare)
        return;

    $.ajax({
        type: "POST",
        url: "/decont_card/add_cheltuiala",
        data: {
            tip: $('#tip_cheltuiala').val(),
            agent_id: $('#agent_decont').val(),
            suma : $('#suma').val(),
            descriere : $('#descriere').val()
        },
        dataType: "json",
        success: function(data) {
            $("#tabel_cheltuieli").trigger("reloadGrid");
            $('#descriere').val('');
            $('#suma').val('');
            $('#tip_cheltuiala').val(0);
            $('#tip_cheltuiala').selectpicker('refresh');
        },
        error: function(xhr, status, error) {
            var err = JSON.parse(xhr.responseText);
            AfiseazaEroareDecont(err.message);
        }
    });

}

function getAgentTotal() {
    $.ajax({
        type: "POST",
        url: "/decont_card/total",
        data: {agent_id : $('#agent_decont').val() ,show_data:custom_data_set },
        dataType: "json",
        success: function(data) {
            $('#agent_totaluri').html(data.html);
        },
        error: function() {
            console.log('error handing here');
        }
    });
}

function inchideDecontAgent() {
    $.ajax({
        type: "POST",
        url: "/decont_card/inchide_decont",
        data: {agent_id : $('#agent_decont').val()},
        dataType: "json",
        success: function(data) {
            var dec_id = 0;
            if(data && data.decont_id) dec_id = parseInt(data.decont_id);
	        if(isNaN(dec_id) || dec_id == 0) { AfiseazaEroareDecont('Eroare incasare decont !'); return;}
            $('#back_btn').hide();
            $('#next_btn').hide();
            $('#pv_btn').show();
            $('#first_page_btn').show();
            $('#agent_totaluri').off( "dblclick");
            $('#decont_id').val(dec_id);
            $("#agenti_de_decontat").jqGrid('setGridParam', { url: '/decont_card/json/tabel_agenti_de_decontat' ,  postData: {"_search":false,"filters":null} } ).trigger("reloadGrid");
            $('#gs_nume_ag').val('');
        },
        error: function(xhr, status, error) {
            var err = JSON.parse(xhr.responseText);
            $('#agent_totaluri').off( "dblclick");
            $('#back_btn').hide();
            $('#next_btn').hide();
            $('#pv_btn').hide();
            $('#first_page_btn').show();
            AfiseazaEroareDecont(err.message);
        }
    });
}

function AfisareDetaliiExpeditieModalCuID(sel)
{
    $.post('/expeditii/detalii_expeditie/', {expeditie : sel}, function(response) {
        $("#detalii-expeditie .modal-body").html(response);
        $("#detalii-expeditie .modal-title").text(sel);
        $('#detalii-expeditie').modal('show');
    });
}

function UpdateDecontDash() {

    var rdata = $("#data_export").val().trim();

    $('.info-dash  ul.valori span').text('0.00 / 0.00');
    $('.info-dash  ul span').addClass('in-progress');
    $('.info-dash .transport_det, .info-dash .rambursuri_det, .info-dash .chitante_det, .info-dash .cheltuieli_det, .info-dash .total_casa_det').text('0.00 / 0.00');

    var transport_de_decontat = 0.00;
    var ramburs_de_decontat = 0.00;
    var chitante_de_decontat = 0.00;
    var total_de_decontat = 0.00;

    var transport_decontat = 0.00;
    var ramburs_decontat = 0.00;
    var chitante_decontat = 0.00;
    var cheltuieli_decontat = 0.00;
    var total_decontat = 0.00;

    $.ajax({
        type: "POST",
        url: "/decont_card/get_decont_dashboard?show_data="+rdata,
        dataType: "json",
        success: function(data) {

            transport_de_decontat = data.agenti_de_decontat.total_transport;
            ramburs_de_decontat = data.agenti_de_decontat.total_ramburs;
            chitante_de_decontat = data.agenti_de_decontat.total_chitante;
            total_de_decontat = data.agenti_de_decontat.total_de_decontat;

            transport_decontat = data.agenti_decontati.total_transport;
            ramburs_decontat = data.agenti_decontati.total_ramburs;
            chitante_decontat = data.agenti_decontati.total_chitante;
            total_decontat = data.agenti_decontati.total_decontat;

            cheltuieli_decontat = data.agenti_decontati.total_cheltuieli;

            $('.info-dash ul.valori span.agenti').text(data.agenti_decontati.nr_agenti + ' / ' + data.agenti_de_decontat.nr_agenti);
            $('.info-dash ul.valori span.transporturi').text(transport_decontat + ' / ' + transport_de_decontat);
            $('.info-dash ul.valori span.rambursuri').text(ramburs_decontat + ' / ' + ramburs_de_decontat);
            $('.info-dash ul.valori span.chitante').text(chitante_decontat + ' / ' + chitante_de_decontat);
            $('.info-dash ul.valori span.total').text(total_decontat + ' / ' + total_de_decontat);
            $('.info-dash ul.valori span.cheltuieli').text(cheltuieli_decontat);
            $('.info-dash ul.valori span.total_casa').text(data.agenti_decontati.total_casa);
            $('.info-dash ul span').removeClass('in-progress');

            $('.info-dash .transporturi_det').text(data.agenti_decontati.nr_transport + ' / '+ data.agenti_de_decontat.nr_transport);
            $('.info-dash .rambursuri_det').text(data.agenti_decontati.nr_ramburs + ' / '+ data.agenti_de_decontat.nr_ramburs);
            $('.info-dash .chitante_det').text(data.agenti_decontati.nr_chitante + ' / '+ data.agenti_de_decontat.nr_chitante);
            $('.info-dash .cheltuieli_det').text(data.agenti_decontati.nr_cheltuieli);


            // console.log(data);
        },
        error: function() {
            $('.info-dash ul span').removeClass('in-progress');
            console.log('error handing here');
        }
    });
}

function exportDecont() {
    var today = new Date();
    var date = today.getDate()+'.'+(today.getMonth()+1)+'.'+today.getFullYear();
    window.location.href = '/decont_card/export_xls/' + $('#data_export').val()+ '/' + date + '/'+$('#toate_centrele').is(':checked');
}

function reincarcaAgentiDeDecontat() {
    var rdata = $("#data_export").val().trim();
    if(custom_data_set)
        var rdata = custom_data_set;
    $("#agenti_de_decontat").jqGrid('setGridParam', { url: '/decont_card/json/tabel_agenti_de_decontat?show_data='+rdata ,  postData: {"_search":false,"filters":null} } ).trigger("reloadGrid");
    $('#gs_nume_ag').val('');
}

function Grid_CurieriDeDecontat() {
    var grid = $("#agenti_de_decontat");
    grid.jqGrid(
        {
            url : '/decont_card/json/tabel_agenti_de_decontat?show_data='+custom_data_set,
            datatype : "json",
            colModel : [ {
                label: 'Agent',
                name : 'nume_ag',
                index : 'ag.nume_ag',
                sortable:false,
                width: 80
            },{
                label: 'Telefon',
                name : 'telefon',
                sortable:false,
				search:false,
                align: 'center',
                width: 40
            }, {
                label : 'Transport',
                name : 'cash',
                sortable:false,
				search:false,
                align: 'center',
                width: 40
            }, {
                label: 'Ramburs',
                name : 'ramburs',
                sortable:false,
				search:false,
                align: 'center',
                width: 40
            }, {
                label: 'Ch. fiscale',
                name : 'client',
                sortable:false,
				search:false,
                align: 'center',
                width: 40
            }, {
                name : 'Decontat',
                index : 'decontat',
                sortable:false,
                stype : 'select',
                formatter: 'select',
                editoptions : {
                    value : ":Toate;0:Nu;1:Da"
                },
                searchoptions : {
                    value : ":Toate;0:Nu;1:Da",
                    sopt : [ 'eq' ]
                },
                align: 'center',
                width: 30
            },{
                label: 'Decont ID',
                name : 'decont_id',
                align: 'center',
                sortable:false,
				search:false,
                width: 60
            },{
                label: 'Decontat(lei)',
                name : 'total_decontat',
                align: 'right',
                sortable:false,
				search:false,
                width: 40
            } ],
            scroll : true,
		    rowNum : 100,
            width : 970,
            height : 800,
            mtype : "GET",
            rownumbers : true,
            gridview : true,
            sortname : 'id',
            viewrecords : true,
            sortorder : "DESC",
            caption : '',
            multiselect : false,
            subGrid: true,
            subGridRowExpanded: function(subgrid_id, row_id) {
                var subgrid_table_id;
                subgrid_table_id = subgrid_id+"_t";
                $("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table>");
                var rdata = $("#data_export").val().trim();
                if(custom_data_set)
                    var rdata = custom_data_set;
                jQuery("#"+subgrid_table_id).jqGrid({
                    url : '/decont_card/json/subgrid_agenti_de_decontat/'+row_id+'?show_data='+rdata,
                    datatype : "json",
                    colModel : [ {
                        label : 'Fct./Chit.',
                        name : 'fctChit',
                        search: false,
                        sortable:false,
                        align: 'left',
                    },{
                        label : 'Valoare',
                        name : 'valoare',
                        search: false,
                        sortable:false,
                        align: 'right',
                    },{
                        label : 'NT/Client',
                        name : 'ntsClient',
                        search: false,
                        sortable:false,
                        align: 'right',
                    },{
                        label : 'Tip',
                        name : 'tip',
                        index : 'tip',
                        sortable : false,
                        formatter: 'select',
                        edittype:'select',
                        editoptions : {
                            value : "1:Transport;2:Ramburs;3:Client CTR",
                        },
                        stype : 'select',
                        searchoptions : {
                            value : ":Toate;1:Transport;2:Ramburs;3:Client CTR",
                            sopt : [ 'eq' ]
                        },
                        align: 'center'
                    },{
                        label : 'Operatiune',
                        name : 'operatiune',
                        index : 'operatiune',
                        sortable : false,
                        formatter: 'select',
                        edittype:'select',
                        editoptions : {
                            value : "1:Colectare;2:Livrare;3:Client CTR",
                        },
                        stype : 'select',
                        searchoptions : {
                            value : ":Toate;1:Colectare;2:Livrare;3:Client CTR",
                            sopt : [ 'eq' ]
                        },
                        align: 'center', 
                    }, {
                        label : 'Data inc.',
                        name : 'dataInc',
                        sortable : false,
                        search: false,
                        sorttype : 'date',
                        formatter : 'date',
                        formatoptions : {
                            srcformat : 'Y-m-d H:i:s',
                            newformat : 'd.m.Y H:i'
                        },
                        align: 'center'
                    },{
                        label : 'vt',
                        name : 'vt',
                        hidden: true
                    }],
                    scroll : true,
		            rowNum : 100,
                    rownumbers : true,
                    footerrow: true,
                    userDataOnFooter: true,
                    height: 400,
                    multiselect: false,
                })
                .jqGrid('filterToolbar', {
                    stringResult : true,
                    searchOnEnter : false
                });
            },
            loadComplete: function() {
                var cRows = this.rows.length, iRow, row, className;

                for (iRow=0; iRow<cRows; iRow++) {
                    row = this.rows[iRow];
                    className = row.className;
                    if ($.inArray('jqgrow', className.split(' ')) > 0) {
                        var status = $(row.cells[6]).html();
                        if (status == "Da") {
                            if ($.inArray('decontatRowClass', className.split(' ')) === -1) {
                                row.className = className + ' decontatRowClass';
                            }
                        }
                    }
                }
            }
        })
        .jqGrid('bindKeys')
        .jqGrid('filterToolbar', {
            stringResult : true,
            searchOnEnter : false
        });
}

function validateCheltuiala(evt) {
    var theEvent = evt || window.event;
    var key = theEvent.key || theEvent.which || theEvent.keyCode;

    if(key == 'Enter' || key == 13){
        AdaugaCheltuiala();
    }
}

function printPVAgent(decont_id = 0){
    var cond = '&decont_id=' + decont_id;
    $.download('decont_card/print', cond, false);
}

function getColumnIndexByName(mygrid,columnName) {
    var cm = mygrid.jqGrid('getGridParam','colModel');
    for (var i=0,l=cm.length; i<l; i++) {
        if (cm[i].name===columnName) {
            return i; // return the index
        }
    }
    return -1;
};