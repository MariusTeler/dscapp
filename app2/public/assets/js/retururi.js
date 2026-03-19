/* //////////////////////////////////////////////////////
				START RETURURI
//////////////////////////////////////////////////////  */

function AfisareUrmarireRetururi() {
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var status = $('#status_ret').val();
	jQuery("#urmarire_retururi").jqGrid(
			'setGridParam',
			{
				url : HTTP + 'retururi/json/urmarire?data_start='
						+ escape(data_start) + '&data_final='+ escape(data_final)+ '&status_ret='+ status
			}).trigger("reloadGrid");
}

function ExportUrmarireRetururi()
{
	var cond = '';
	var status = $('#status_ret').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();

	cond += '&data_start=' + data_start + '&data_final='+ data_final+'&status_ret='+status;

	var mypostdata = $("#urmarire_retururi").jqGrid('getGridParam', 'postData');

	$.downloadWithFilters('retururi/export_urmarire', cond, mypostdata._search, mypostdata.filters);

}

function ModificareExpeditieRetururi(exp){
	var status = $('#status_exp_'+exp).val();
	$.post(HTTP + 'retururi/modificare_expeditie/'+exp+'/'+status,{}, function(response) {
		AfisareUrmarireRetururi();
	});
}

function AfisareDetaliiExpeditieRetur(sel){
	$('#progressbar').hide();
	$("#id_exp").val(sel);
	$.post(HTTP + 'retururi/detalii_expeditie/', {
		exp : sel
	}, function(response) {
		$("#detalii_exp").html('NT initiala: '+sel);
		$("#detaliii_exp_ramburs").html('NT referinta: '+response);
	});
}

var rowsToColor = [];
function Retururi_Grid_Urmarire()
{
	SetareDate();
	var grid = jQuery('#urmarire_retururi');
	getColumnIndexByName = function(mygrid,columnName) {
        var cm = mygrid.jqGrid('getGridParam','colModel');
        for (var i=0,l=cm.length; i<l; i++) {
            if (cm[i].name===columnName) {
                return i; // return the index
            }
        }
        return -1;
    };

	grid.jqGrid(
	{
		url : HTTP + 'retururi/json/urmarire',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Ce. Liv.', 'Ce. Col.', 'Nr. Exp', 'Col. Exp','Nr. Ret/Doc', 'Col. Ret/Doc','Tip','Status','Expeditor','Destinatar','Optiuni' ],
		colModel : [ {
					name : 'destinatar_centru',
					index : 'ced.nume',
					width: 40
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 40
				}, {
					name : 'expeditie',
					index : 'a.expeditie',
					sorttype : 'int',
					width: 60,
					align: 'right'
				}, {
					name : 'data_expeditie',
					index : 'a.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center'
				},{
					name : 'bref',
					search: false,
					sortable:false,
					width: 120,
					align: 'right'
				}, {
					name : 'bdata_expeditie',
					search: false,
					sortable:false,
					width: 120,
					align: 'center'
				}, {
					name : 'tip_exp',
					index : 'a.tip_exp',
					stype : 'select',
					searchoptions : {
						value : ":Toate;1:NT;2:DOC;3:NT+DOC;6:AMB",
						sopt : [ 'eq' ]
					},
					align: 'right',
					width: 50
				}, {
					name : 'status',
					index : 'a.status_retururi',
					stype : 'select',
					formatter: 'select',
                    editoptions : {
                    	value : ":Toate;0:In Derulare;1:Inchis NT;2:Inchis DOC;3:Inchis NT+DOC"
                    },
					searchoptions : {
						value : ":Toate;0:In Derulare;1:Inchis NT;2:Inchis DOC;3:Inchis NT+DOC",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 80
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 110
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 110
				},{ name: 'optiuni',
					edittype: 'optiuni',
					width: 100,
					sortable: false
		} ],
		rowNum : 50,
		rowList : [ 50, 100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : 'ced.nume,a.data_expeditie,cld.nume',
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		onSelectRow : function(id) {
			AfisareDetaliiExpeditieRetur(id);
		},
		ondblClickRow : function(id) {
			AfisareDetaliiExpeditieCuID(id)
		},
	    loadComplete: function() {
	            var iCol = getColumnIndexByName($(this),'status'),
	                cRows = this.rows.length, iRow, row, className;

	            for (iRow=0; iRow<cRows; iRow++) {
	                row = this.rows[iRow];
	                className = row.className;
	                if ($.inArray('jqgrow', className.split(' ')) > 0) { // $(row).hasClass('jqgrow')
	                	var status = $(row.cells[iCol]).html();
	                    if (status == "IN DERULARE") {
	                        if ($.inArray('myAltRowClass', className.split(' ')) === -1) {
	                            row.className = className + ' myAltRowClass';
	                        }
	                    }
	                }
	            }
	       },
	    gridComplete: function() {
            var ids = grid.jqGrid('getDataIDs');
            for (var i = 0; i < ids.length; i++) {
                var cl = ids[i];
                checkout = '<select style="width: 100px; float:left;" id="status_exp_'+cl+'" name="status_exp_'+cl+'" title="" >'+
		                	'<option selected="selected" value="5">Status Nou</option>'+
		                	'<option value="0">In Derulare</option>'+
		                	'<option value="1">Inchis NT</option>'+
		                	'<option value="2">Inchis DOC</option>'+
		                	'<option value="3">Inchis NT+DOC</option>'+
		            		'</select>'+
							'<input type="button" name="search" value="Modifica"  onclick="ModificareExpeditieRetururi('+cl+');" style="margin-left: 10px;  float:left;"/>';
                grid.jqGrid('setRowData', ids[i], { optiuni: checkout });
            }
        },
        footerrow: true,
	    userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function Retururi_Grid_Vizualizare()
{
	SetareDate();
	var grid = jQuery('#urmarire_retururi');
	getColumnIndexByName = function(mygrid,columnName) {
        var cm = mygrid.jqGrid('getGridParam','colModel');
        for (var i=0,l=cm.length; i<l; i++) {
            if (cm[i].name===columnName) {
                return i; // return the index
            }
        }
        return -1;
    };

	grid.jqGrid(
	{
		url : HTTP + 'retururi/json/urmarire',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Ce. Liv.', 'Ce. Col.', 'Nr. Exp', 'Col. Exp','Nr. Ret/Doc', 'Col. Ret/Doc', 'Tip','Status','Expeditor','Destinatar'],
		colModel : [ {
					name : 'destinatar_centru',
					index : 'ced.label',
					width: 40
				}, {
					name : 'expeditor_centru',
					index : 'cee.label',
					width: 40
				}, {
					name : 'expeditie',
					index : 'a.expeditie',
					sorttype : 'int',
					width: 70,
					align: 'right'
				}, {
					name : 'data_expeditie',
					index : 'a.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center'
				},{
					name : 'bref',
					search: false,
					sortable:false,
					width: 120,
					align: 'right'
				}, {
					name : 'bdata_expeditie',
					search: false,
					sortable:false,
					width: 120,
					align: 'center'
				}, {
					name : 'tip_exp',
					index : 'a.tip_exp',
					stype : 'select',
					searchoptions : {
						value : ":Toate;1:NT;2:DOC;3:NT+DOC;6:AMB",
						sopt : [ 'eq' ]
					},
					align: 'right',
					width: 50
				}, {
					name : 'status',
					index : 'a.status_retururi',
					stype : 'select',
					formatter: 'select',
                    editoptions : {
                    	value : ":Toate;0:In Derulare;1:Inchis NT;2:Inchis DOC;3:Inchis NT+DOC"
                    },
					searchoptions : {
						value : ":Toate;0:In Derulare;1:Inchis NT;2:Inchis DOC;3:Inchis NT+DOC",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 80
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 145
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 145
				}
				],
		rowNum : 50,
		rowList : [ 50, 100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : 'ced.nume,a.data_expeditie,cld.nume',
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		onSelectRow : function(id) {
			AfisareDetaliiExpeditieRetur(id);
		},
		ondblClickRow : function(id) {
			AfisareDetaliiExpeditieCuID(id)
		},
	    loadComplete: function() {
	            var iCol = getColumnIndexByName($(this),'status'),
	                cRows = this.rows.length, iRow, row, className;

	            for (iRow=0; iRow<cRows; iRow++) {
	                row = this.rows[iRow];
	                className = row.className;
	                if ($.inArray('jqgrow', className.split(' ')) > 0) { // $(row).hasClass('jqgrow')
	                	var status = $(row.cells[iCol]).html();
	                    if (status == "IN DERULARE") {
	                        if ($.inArray('myAltRowClass', className.split(' ')) === -1) {
	                            row.className = className + ' myAltRowClass';
	                        }
	                    }
	                }
	            }
	       },
        footerrow: true,
	    userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

/* //////////////////////////////////////////////////////
				END RETURURI
//////////////////////////////////////////////////////  */

/* //////////////////////////////////////////////////////
				START RAMBURSURI
//////////////////////////////////////////////////////  */

function AfisareUrmarireRambursuri(section) {
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var status = $('#status_rmb').val();
	var date_range_for = $('#date_range_for').val();
	var expeditii = $('#expeditii').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');
	var mUrl = HTTP + 'rambursuri/json/urmarire?data_start=' + escape(data_start) + '&data_final='+ escape(data_final)+ '&status_rmb='+ status + '&date_range_for=' + date_range_for
	
	if(expeditii.length > 6){
		history.pushState(null, null, 'rambursuri/'+section+'?expeditii='+expeditii);
		mUrl = HTTP + 'rambursuri/json/urmarire?expeditii='+expeditii;
	}
	else
		history.pushState(null, null, 'rambursuri/'+section+'?data_start='+data_start+'&data_final='+data_final+'&status='+status+'&date_range_for='+date_range_for);


	jQuery("#urmarire_rambursuri").jqGrid('setGridParam',{url : mUrl}).trigger("reloadGrid");
}

function PrintUrmarireRambursuri(){
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var status = $('#status_rmb').val();
	var date_range_for = $('#date_range_for').val();
	var expeditii = $('#expeditii').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');

	var mypostdata = $("#urmarire_rambursuri").jqGrid('getGridParam', 'postData');

	$.post(HTTP + 'rambursuri/print_urmarire/?data_start='+ data_start + '&data_final='+ data_final +'&status_rmb='+status + '&expeditii='+ expeditii + '&date_range_for=' + date_range_for,
		{'_search' : mypostdata._search, 'filters': mypostdata.filters},function(response) {
		$("#detalii_expeditie").html(response);
		$('#detalii_expeditie').printElement(
		{
            leaveOpen:false,
            printMode:'iframe',
            pageTitle: 'Urmarire rambursuri',
            overrideElementCSS:[
				HTTP+'assets/css/style_screen.css'
				,{href:HTTP+'assets/css/style_print.css',media:'print'}
				]
        });
        $("#detalii_expeditie").dialog("destroy");
        $("#detalii_expeditie").html('');
	});


	$('#form_gs_centru_livrare').val($('#gs_centru_livrare').val());
	$('#form_gs_centru_colectare').val($('#gs_centru_colectare').val());
	$('#form_gs_expeditor').val($('#gs_expeditor').val());
	$('#form_gs_destinatar').val($('#gs_destinatar').val());
	$('#form_gs_livrat').val($('#gs_livrat').val());
	$('#form_gs_status').val($('#gs_status').val());
}

function ExportUrmarireRambursuri()
{
	var cond = '';
	var status = $('#status_rmb').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var expeditii = $('#expeditii').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');
	var date_range_for = $('#date_range_for').val();

	cond += '&data_start=' + data_start + '&data_final='+ data_final +'&status_rmb='+status + '&expeditii='+expeditii + '&date_range_for=' + date_range_for;

	var mypostdata = $("#urmarire_rambursuri").jqGrid('getGridParam', 'postData');

	$.downloadWithFilters('rambursuri/export_urmarire', cond, mypostdata._search, mypostdata.filters);
}

function AfisareDetaliiExpeditieRamburs(sel){
	$('#progressbar').hide();
	$("#id_exp").val(sel);
	$.post(HTTP + 'rambursuri/detalii_expeditie/', {
		exp : sel
	}, function(response) {
		$("#detalii_exp").html('NT initiala: '+sel);
		$("#detaliii_exp_ramburs").html('NT referinta: '+response);
	});
}

var rowsToColor = [];
function Rambursuri_Grid_Urmarire(section)
{
	var paginatie_sec = '';
    var extra_fil = '';
	SetareDate();
    var grid = jQuery('#urmarire_rambursuri');
    var urmarire_url = 'rambursuri/json/urmarire';
	var orderBy = 'init.data_expeditie';
	if(section == 'rambursuri_nelivrate' || section == 'rambursuri_netrimise'){
        var grid = jQuery('#' + section);
        paginatie_sec = '_'+section;
        extra_fil = '&tip_plata=4'
        urmarire_url = 'dashboard/json/'+section+'/' + $('#centru').val() ;
		if(section == 'rambursuri_nelivrate')
			orderBy = 'rbs.data_expeditie';
	}

	getColumnIndexByName = function(mygrid,columnName) {
        var cm = mygrid.jqGrid('getGridParam','colModel');
        for (var i=0,l=cm.length; i<l; i++) {
            if (cm[i].name===columnName) {
                return i; // return the index
            }
        }
        return -1;
    };

	var table_filters = $('#table_filters').val();

	var table_filters_url = "";
	var _search = false;
	var rbs_centru_id = 0;
	if(table_filters.length > 0){
        table_filters_url = b64DecodeUnicode(table_filters);
        _search = true;
        rbs_centru_id = $('#rbs_centru_id').val();
    }


    grid.jqGrid(
	{
		url : HTTP + urmarire_url + '/?data_start=' + escape($('#data_start').val()) + '&data_final='+ escape($('#data_final').val())+ '&status_rmb='+ $('#status_rmb').val() + '&date_range_for=' + $('#date_range_for').val() + extra_fil
        ,
		datatype: 'json',
        mtype: 'POST',
        postData: {
            filters:table_filters_url
        },
        search:_search,
        colNames : [ 'Centru Livrare', 'Centru Colectare', 'Nr. Exp', 'Col. Exp', 'Nr. Rmb','Col. Rmb','Tip Ret.','Valoare','Tip plata', 'Status','Expeditor','Destinatar', 'Platitor', 'Scanari'],
		colModel : [ 
				{
					name : 'destinatar_centru',
					index : 'ced.nume',
					width: 70
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 70
				}, {
					name : 'expeditie',
					index : 'init.expeditie',
					sorttype : 'int',
					width: 55,
					align: 'right'
				}, {
					name : 'data_expeditie',
					index : 'init.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center'
				}, {
					name : 'referire',
					index : 'rbs.expeditie',
					sorttype : 'int',
					width: 55,
					align: 'right',
					classes: ['exp_referire_class']

				}, {
					name : 'data_ramburs',
					index : 'rbs.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center',
					classes: ['exp_data_referire_class']

				}, {
					name : 'rbs_tip_exp',
					index : 'rbs.tip_exp',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : "0:Initiala;1:Retur NT;2:Retur doc.;3:Ramburs;4:Interna;5:Returnare;6:Retur ambalaj"
					},
					searchoptions : {
						value : ":Toate;0:Fara nota;3:Ramburs;5:Returnare",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 50,
            		classes: ['rbs_tip_exp_referire_class']
				}, {
					name : 'ramburs',
					index : 'init.ramburs',
					sorttype : 'int',
					align: 'right',
					width: 40
				},{
					name : 'tip_plata',
					index : 'init.tip_plata',
					stype : 'select',
					formatter: 'select',
						editoptions : {
								value : ":Toate;0:cash;3:cont;1:bo;2:cec;4:cash/cont"
						},
					searchoptions : {
						value : ":Toate;0:cash;3:cont;1:bo;2:cec;4:cash/cont",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 50
				}, {
					name : 'status',
					index : 'init.status_ramburs',
					stype : 'select',
					searchoptions : {
						value : ":Toate;0:In Derulare;2:Validat;4:Spre client;1:Inchis;5:Anulat;6:Litigiu;7:Pierdut;10:Nepreluat;11:Spre compensare;21:On Hold;23:Decontat;24:LaPlata;25:Nesosit;26:Abandonat;27:Compensat;30:Aprobat;31:Returnat",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 70
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 80
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 80
				},{
					name : 'platitor',
					index : 'clp.nume',
					width: 80
				},{
					name : 'scanari',
					stype : 'select',
					formatter: 'select',
                    editoptions : {
                        value : ":All;0:NU;1:DA"
                    },
					searchoptions : {
						value : ":All;0:NU;1:DA",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 35
				}],
		rowNum : 50,
		rowList : [ 50,100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : orderBy,
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie'+paginatie_sec,
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		multiselect: true,
		beforeSelectRow: function (rowid, e) {
                    var $myGrid = $(this),
                        i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
                        cm = $myGrid.jqGrid('getGridParam', 'colModel'),
                        cellcontent = $myGrid.getCell(rowid,i);
                    if((i == 4 || i == 6) && cellcontent !='' && cellcontent != "&nbsp;")
						AfisareDetaliiExpeditieCuID(cellcontent);
                    return (cm[i].name === 'cb');
        },
	    loadComplete: function() {
            UrmarireVizualizareRambursuriCulori(this);
			var userData = grid.jqGrid('getGridParam', 'userData');
			jQuery("#nr_exps_in").html("Numar expeditii : 0");
			if(userData['nr_exps_in'])
				jQuery("#nr_exps_in").html("Numar expeditii : "+userData['nr_exps_in']);
            if(userData['filters'] && section != 'rambursuri_netrimise' && section != 'rambursuri_nelivrate'){
                var filters = userData['filters'];
                $('#table_filters').val(filters);
                var data_start = $('#data_start').val();
                var data_final = $('#data_final').val();
                var status = $('#status_rmb').val();
                var date_range_for = $('#date_range_for').val();
                history.pushState(null, null, 'rambursuri/'+section+'?data_start='+data_start+'&data_final='+data_final+'&status='+status+'&date_range_for='+date_range_for+'&filters='+filters);
            }
            if(section == 'rambursuri_netrimise' || section == 'rambursuri_nelivrate'){
                $('#box_'+section).removeClass('disabled-values');
                $('#box_'+section).removeClass('loading_box');
                var records = $("#"+section).getGridParam("records");
                var user_data = $("#"+section).getGridParam("userData");
                $('#'+section+'_count, #'+section+'_exp').text(records);
                $('#'+section+'_val').text(user_data.total_ramburs);
                $('#li_'+section).show();

			}
		},
        footerrow: true,
	    userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});


    grid.jqGrid('navGrid','#paginatie'+paginatie_sec,{add:false,edit:false,del:false,search:true,refresh:false},
        {},{},{},{multipleSearch:true});


}

function ModificareMultiplaExpeditieRamburs(){
	var status = $('#status_rmb_set').find(":selected").val();
	if(status == 999) { AfiseazaEroare("Selectioneaza noul status !"); return false; }

	var ramb_grid = jQuery('#urmarire_rambursuri');
	var sel_ids = ramb_grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum o expeditie !"); return false; }
	else if(sel_ids.length>300) { AfiseazaEroare("Nu se pot selectiona mai mult de 300 de expeditii !"); return false; }
	var postData = {};

	for(var $i=0, $l=sel_ids.length; $i<$l; $i++) postData[$i] = sel_ids[$i];
	var postData = JSON.stringify(postData);

	$.ajax({
		type: "POST",
		url: HTTP + 'rambursuri/modificare_status_multiple',
		data: { status: status, expeditii: postData },
		dataType: "json",
		success: function(response) {
			if(response.errorCode > 0) {
				AfiseazaEroare(response.errorMsg + (response.records.length > 0 ? ("\n" + response.records) : ""));
			}
			AfisareUrmarireRambursuri();
		},
		error: function() {
			AfiseazaEroare("Server is down");
		}
	});
}

function Rambursuri_Grid_Vizualizare()
{
	SetareDate();
	var grid = jQuery('#urmarire_rambursuri');
	getColumnIndexByName = function(mygrid,columnName) {
        var cm = mygrid.jqGrid('getGridParam','colModel');
        for (var i=0,l=cm.length; i<l; i++) {
            if (cm[i].name===columnName) {
                return i; // return the index
            }
        }
        return -1;
    };

	grid.jqGrid(
	{
		url : HTTP + 'rambursuri/json/urmarire',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Centru Livrare', 'Centru Colectare', 'Nr. Exp', 'Col. Exp', 'Nr. Rmb','Col. Rmb','Tip Ret.','Valoare','Tip plata', 'Status','Expeditor','Destinatar'],
		colModel : [ {
					name : 'destinatar_centru',
					index : 'ced.nume',
					width: 75
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 75
				}, {
					name : 'expeditie',
					index : 'init.expeditie',
					sorttype : 'int',
					width: 70,
					align: 'right'
				}, {
					name : 'data_expeditie',
					index : 'init.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center'
				}, {
					name : 'referire',
					index : 'rbs.expeditie',
					sorttype : 'int',
					width: 70,
					align: 'right',
            		classes: ['exp_referire_class']
				}, {
					name : 'data_ramburs',
					index : 'rbs.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center',
            		classes: ['exp_data_referire_class']
				}, {
					name : 'rbs_tip_exp',
					index : 'rbs.tip_exp',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : ":0:Initiala;1:Retur NT;2:Retur doc.;3:Ramburs;4:Interna;5:Returnare;6:Retur ambalaj"
					},
					searchoptions : {
						value : ":Toate;3:Ramburs;5:Returnare",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 70,
            		classes: ['rbs_tip_exp_referire_class']
				}, {
					name : 'ramburs',
					index : 'init.ramburs',
					sorttype : 'int',
					align: 'right',
					width: 50
				},{
					name : 'tip_plata',
					index : 'init.tip_plata',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : ":Toate;0:cash;3:cont;1:bo;2:cec;4:cash/cont"
					},
					searchoptions : {
						value : ":Toate;0:cash;3:cont;1:bo;2:cec;4:cash/cont",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 70
				}, {
					name : 'status',
					index : 'init.status_ramburs',
					stype : 'select',
					searchoptions : {
						value : ":Toate;0:In Derulare;2:Validat;4:Spre client;1:Inchis;5:Anulat;6:Litigiu;7:Pierdut;10:Nepreluat;11:Spre compensare;21:On Hold;23:Decontat;24:LaPlata;25:Nesosit;26:Abandonat;27:Compensat;30:Aprobat;31:Returnat",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 70
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 145
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 145
				}],
		rowNum : 50,
		rowList : [ 50, 100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : 'ced.nume,init.data_expeditie,cld.nume',
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		onCellSelect: function(rowid, iCol, cellcontent, e) {
                if((iCol == 3 || iCol == 5) && cellcontent !='' && cellcontent != "&nbsp;")
					AfisareDetaliiExpeditieCuID(cellcontent);
        },
        loadComplete: function() {
            UrmarireVizualizareRambursuriCulori(this);
        },
        footerrow: true,
	    userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function UrmarireVizualizareRambursuriCulori(elem) {

	var user_data = $(elem).getGridParam("userData");
	if(user_data.rambursuri_netrimise_date_range){
		$('#rambursuri_netrimise_date_range').text(user_data.rambursuri_netrimise_date_range);
	}
    if(user_data.rambursuri_nelivrate_date_range){
        $('#rambursuri_nelivrate_date_range').text(user_data.rambursuri_nelivrate_date_range);
    }

    var iCol = getColumnIndexByName($(elem),'status'),
        cRows = elem.rows.length, iRow, row, className;

    for (iRow=0; iRow<cRows; iRow++) {
        row = elem.rows[iRow];
        className = row.className;
        if ($.inArray('jqgrow', className.split(' ')) > 0) {
            var status = $(row.cells[iCol]).html();
            if (status == "IN DERULARE") {
                if ($.inArray('myAltRowClass', className.split(' ')) === -1) {
                    row.className = className + ' myAltRowClass';
                }
			}
			if (status == "DECONTAT") {
                if ($.inArray('decontatRowClass', className.split(' ')) === -1) {
                    row.className = className + ' decontatRowClass';
                }
            }
            if (status == "SPRE CLIENT") {
                if ($.inArray('spreClientRowClass', className.split(' ')) === -1) {
                    row.className = className + ' spreClientRowClass';
                }
            }
            if (status == "VALIDAT") {
                if ($.inArray('validatRowClass', className.split(' ')) === -1) {
                    row.className = className + ' validatRowClass';
                }
            }
            if (status == "INCHIS") {
                if ($.inArray('inchisRowClass', className.split(' ')) === -1) {
                    row.className = className + ' inchisRowClass';
                }
            }
            if (status == "REFUZAT") {
                if ($.inArray('refuzatRowClass', className.split(' ')) === -1) {
                    row.className = className + ' refuzatRowClass';
                }
            }
        }
    }

    var iCol = getColumnIndexByName($(elem),'rbs_tip_exp'),
        cRows = elem.rows.length, iRow, row, className;

    for (iRow=0; iRow<cRows; iRow++) {
        row = elem.rows[iRow];
        className = row.className;
        if ($.inArray('jqgrow', className.split(' ')) > 0) { // $(row).hasClass('jqgrow')
            var tip = $(row.cells[iCol]).html();

            if (tip != "Ramburs") {
                if ($.inArray('exp_returnat', className.split(' ')) === -1) {
                    row.className = className + ' exp_returnat';
                }
            }
        }
    }
}

function SchimbaStatusRambursuri(sel){
	if(sel==4 || sel==9){
		$('#inchide_rambursuri').show();
	}else{
		$('#inchide_rambursuri').hide();
	}
}

/* //////////////////////////////////////////////////////
				END RAMBURSURI
//////////////////////////////////////////////////////  */

/* //////////////////////////////////////////////////////
					START CAUTARE RBS DECONT
//////////////////////////////////////////////////////  */
function CautareRambursuriDecont(sel) {
	if (sel == 1) {
		if ($('#perioada').is(':checked')) {
			$('#data_start').removeAttr('disabled');
			$('#data_final').removeAttr('disabled');
			$('.ui-datepicker-trigger').show();
		} else {
			$('#data_start').attr('disabled', true);
			$('#data_final').attr('disabled', true);
			$('.ui-datepicker-trigger').hide();
		}
	} else if (sel == 2) {
		if ($('#curier').is(':checked')) {
			$('#curier_livrare_nume').removeAttr('disabled');
		} else {
			$('#curier_livrare').val('');
			$('#curier_livrare_nume').val('');
			$('#curier_livrare_nume').attr('disabled', true);
		}
	} else if (sel == 3) {
		if ($('#client').is(':checked')) {
			$('#expeditor_nume').removeAttr('disabled');
		} else {
			$('#expeditor').val('');
			$('#expeditor_nume').val('');
			$('#expeditor_nume').attr('disabled', true);
		}
	}
}

function AfisareRambursuriDecont()
{
	var cond = '';
	if($.trim($('#search').val()).length) {
		cond += '&operatiune=' + escape($('#operatiune').val()) + '&search='+ escape($.trim($('#search').val()));
	}

	if ($('#perioada').is(':checked') && $.trim($('#data_start').val()).length && $.trim($('#data_final').val()).length) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	}
	if ($('#curier').is(':checked') && $.trim($('#curier_livrare').val()).length) {
		var curier_id = parseInt($('#curier_livrare').val());
		if(!isNaN(curier_id) && curier_id > 0)
			cond += '&cod_ag=' + curier_id;
	}
    if ($('#client').is(':checked') && $.trim($('#expeditor').val()).length) {
		var expeditor_id = parseInt($('#expeditor').val());
		if(!isNaN(expeditor_id) && expeditor_id > 0)
			cond += '&cod_cl=' + expeditor_id;
	}

	var exps = $('#expeditii_area').val().split(new RegExp(/[\r\n\x0B\x0C\u0085\u2028\u2029,]+/g)).filter(function (el) {
		return el != null && el != "";
	});

	$("#text_lines").text(exps.length);
	postExpeditii = $('#expeditii_area').val().replace(/[\r\n\x0B\x0C\u0085\u2028\u2029,]+/g, ',');

	if($.trim(postExpeditii).length)
		jQuery("#jqGrid_decont_cautare_rambursuri").jqGrid('setGridParam', { url: HTTP + 'rambursuri/decont/json_cautare', postData : { expeditii: postExpeditii }}).trigger("reloadGrid");
	else if($.trim(cond).length)
		jQuery("#jqGrid_decont_cautare_rambursuri").jqGrid('setGridParam',{ url: HTTP + 'rambursuri/decont/json_cautare?a' + cond, postData : { expeditii: "" }}).trigger("reloadGrid");
}

function Grid_RambursuriCautareDecont()
{
	var grid = jQuery('#jqGrid_decont_cautare_rambursuri');

	grid.jqGrid(
	{
		url : HTTP + 'rambursuri/decont/json_cautare',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'CH. RBS.', 'Valoare', 'Tip plata', 'Data', 'Nr. Exp', 'Expeditor', 'Destinatar','C.Exp','C.Dest', 'Agent', 'Primitor', 'Incasat', 'Decontata'],
		colModel : [ {
			name : 'ch_ramburs',
			index : 'dr.ch_ramburs',
			width: 100
		},{
			name : 'ramburs',
			index : 'dr.ramburs',
			align: 'right',
			width: 50
		},{
			name : 'tip_plata',
			index : 'e.tip_plata',
			stype : 'select',
			formatter: 'select',
			editoptions : {
				value : ":Toate;0:cash;3:cont"
			},
			searchoptions : {
				value : ":Toate;0:cash;3:cont",
				sopt : [ 'eq' ]
			},
			align: 'center',
			width: 40
		}, {
			name : 'data',
			index : 'dr.dataInc',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		},{
			name : 'expeditie',
			index : 'de.expeditie',
			sorttype : 'int',
			width: 60
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			width: 100
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 100
		}, {
			name : 'expeditor_centru',
			index : 'cee.label',
			width: 30,
			align : 'center'
		}, {
			name : 'destinatar_centru',
			index : 'ced.label',
			width: 30,
			align : 'center'
		}, {
			name : 'agent',
			index : 'ag.nume_ag',
			width: 100
		}, {
			name : 'primitor',
			index : 'e.primitor',
			width: 100
		}, {
			name : 'tipPlata',
			index : 'tipPlata',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:CASH;1:CARD",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:CASH;1:CARD"
			},
			align: 'center',
			width: 40
		}, {
			name : 'decontata',
			index : 'decontata',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:Nu;1:Da",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:Nu;1:Da"
			},
			align: 'center',
			width: 35
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : "dr.dataInc",
		sortorder : "desc",
		viewrecords : true,
		gridview : true,
		pager : '#jqGrid_paginatie',
		shrinkToFit: false,
		caption: 'Lista chitante rambursuri',
		forceFit: true,
		multiselect: true,
		footerrow: true,
	    userDataOnFooter: true,
		beforeSelectRow: function (rowid, e) {
			var $myGrid = $(this),
				i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
				cm = $myGrid.jqGrid('getGridParam', 'colModel'),
				cellcontent = $myGrid.getCell(rowid,i);
            if(i == 2)
				AfisareDetaliiRamburs(rowid);
			else if(i == 6 && cellcontent !='' && cellcontent != "&nbsp;")
				AfisareDetaliiExpeditieCuID(cellcontent);
			return (cm[i].name === 'cb');
		},
		loadError : function(xhr,st,err) {
			AfiseazaEroare(xhr.responseText);
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});

}

function PrintareRambursuriDecont(){

	var grid = jQuery('#jqGrid_decont_cautare_rambursuri');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum o linie pentru printare!"); return false; }
	else if(sel_ids.length>200) { AfiseazaEroare("Nu se pot selectiona mai mult de 200 linii pentru printare !"); return false; }
	var postData = {};

	for(var i=0, l=sel_ids.length; i<l; i++) postData[i] = sel_ids[i];
	var postData = JSON.stringify(postData);
	$.download('rambursuri/decont/print/', postData, true);
	return true;
}

function AfisareDetaliiRamburs(id){
	$("#modal-error-message").text("Chitanta invalida");
	$("#modal-error-message").hide();
	$('#dialog-form #agent_id').val("");
	$('#dialog-form #agent').text("");
	$('#dialog-form #factura').text("");
	$('#dialog-form #expeditie').text("");
	$('#dialog-form #ch_ramburs').text("");
	$('#dialog-form #data').text("");
	$('#dialog-form #ramburs').text("");
	$('#dialog-form #client').val("");
	$('#dialog-form #cui').val("");
	$('#dialog-form #adresa').val("");
	$('#dialog-form #tipPlata').val("");
	$('#dialog-form').dialog( "option", "title", "");
	$.get(HTTP + 'rambursuri/decont/detalii/'+id,'', function(response) {
		  var rasp = response.split('|||');
		  PrepareResponse(rasp[0]);
		  /*
		  return '1|||'.$sql['id'].'|||'.$sql['data'].'|||'.$sql['expeditie'].'|||'.$sql['agent_id'].'|||'.$sql['ch_ramburs'].'|||'.$sql['ramburs'].'|||'.$sql['client'].'|||'
				.$sql['cui'].'|||'.$sql['adresa'];
				*/
		$('#dialog-form #id').val(rasp[1]);
		if (rasp[0] == 1){
			var idDel = rasp[1];
			var txtDel = rasp[6];
			$('#dialog-form #agent_id').val(rasp[2]);
			$('#dialog-form #agent').text(rasp[5]);
			$('#dialog-form #ch_ramburs').text(rasp[6]);
			$('#dialog-form #expeditie').text(rasp[4]);
			$('#dialog-form #factura').text(rasp[12]);
			$('#dialog-form #data').text(rasp[3]);
			$('#dialog-form #ramburs').text(rasp[7]);
			$('#dialog-form #tipPlata').text(rasp[11]);

			$('#dialog-form #client').val(rasp[8]);
			$('#dialog-form #cui').val(rasp[9]);
			$('#dialog-form #adresa').val(rasp[10]);
			  
			$('#dialog-form').dialog( "option", "title", "Detalii ramburs nr. : "+rasp[6] );
			var btn_close = {"text": "Inchide [esc]",click: function() {$( this ).dialog( "close" );}}
			var btn_print = {"text": 'Print', "tabIndex": -1, 
				click: function() { PrintRamburs(id); $( this ).dialog( "close" );}
			};
			var btn_xclose = {
				"text": 'DEL', 
				"tabIndex": -1,
				"style" : "margin-right:400px;background:red;color:black",
				click: function() { 
						$( this ).dialog( "close" );
						AnulareRambursDecont(idDel, txtDel);
				}
			};
			$('#dialog-form #agent').prop("readonly", true);
			$('#dialog-form').dialog( "option", "buttons",
						[
							btn_xclose,
							btn_print,
							btn_close
						]
			);
		}
		else {
			 //error
			$("#modal-error-message").text("Chitanta invalida");
			$("#modal-error-message").show();
		}
		$('#dialog-form').dialog("open");
	});
}

function AnulareRambursDecont(idDel, txtDel){
	$('#dialog-confirm').dialog( "option", "title", "Confirmare");
	$('#dialog-confirm #ch').text(txtDel);
	var btn_no = {"text": "NU [esc]",click: function() {$( this ).dialog( "close" );}}
	var btn_yes = {
		"text": "DA",
		"tabIndex": -1, 
		click: function() { 
			$.get(HTTP + 'rambursuri/decont/xclose/'+idDel, '', function(response2) {
				if(response2 == 0) {
					$("#confirm-error-message").text("FAILED");
					$("#confirm-error-message").show();
				}
				else { 
					$('#dialog-confirm').dialog( "close" );
					jQuery("#jqGrid_decont_cautare_rambursuri").trigger("reloadGrid");
				}
				
			})
	}};
	$('#dialog-confirm').dialog( "option", "buttons",
		[
			btn_yes,
			btn_no
		]
	);
	$('#dialog-confirm').dialog("open");
}

function PrintRamburs(id){
	var client = $('#dialog-form #client').val();
	var cui = $('#dialog-form #cui').val();
	var adresa = $('#dialog-form #adresa').val();

	var postData = {};

	postData['id'] = id;
	postData['client'] = client;
	postData['cui'] = cui;
	postData['adresa'] = adresa;
	
	$.download('rambursuri/decont/printone', JSON.stringify(postData), true);
	return true;
}