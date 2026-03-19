function Grid_DecontRambursCentre() 
{
	SetareDate();
	var grid = jQuery('#lista_centre');
	
	grid.jqGrid(
	{
		url : HTTP + 'decont_ramburs/json/centre',
		datatype: 'json',
		mtype: 'POST',
		colNames : ['Cod centru', 'Centru', 'Incasari','Rambursuri','Nr. exps.','Sold','Actiune'],
		colModel : [ {
			name : 'cod',
			index : 'c.label',
			width: 60
		}, {
			name : 'nume',
			index : 'c.nume',
			width: 100,
		}, {
			name : 'incasare',
			index : 'incasare',
			sorttype : 'int',
			width: 70,
			align: 'right'
		}, {
			name : 'rambursuri',
			index : 'rambursuri',
			sorttype : 'int',
			width: 70,
			align: 'right'
		}, {
			name : 'nr_expeditii',
			index : 'nr_expeditii',
			sorttype : 'int',
			width: 70,
			align: 'right'
		}, {
			name : 'sold',
			index : 'e.sold',
			sorttype : 'int',
			width: 70,
			align: 'right'
		}, {
			name : 'actiune',
			align: 'center',
			search: false,
			width: 50
		} ],
		scroll : 1,
		rowNum : 100,
		width : 960,
		height : 'auto',
		sortname : 'c.nume',
		sortorder : "asc",
		viewrecords : false,
		pager : false,
		shrinkToFit: false,
		caption:"",
		forceFit: true,
	  footerrow: true,
	  userDataOnFooter: true,
	  multiselect: false,
	  ondblClickRow : function(id) {
			openTabDecontRambursDetaliiCentru(id);
		}
	});
	grid.jqGrid('bindKeys');
}

function Grid_DecontRambursCentru(id) {
    var grid = jQuery("#detalii_centru");
	    grid.jqGrid(
			{
				url : HTTP + 'decont_ramburs/json/detalii_centru_json?centru='+id,
				datatype : "json",
				colNames : ['Localitate','Destinatar','Nr.Exp', 'Greutate','Plicuri','Colete','Paleti','Km'],
				colModel : [ {
					name : 'destinatar_localitate',
					index : 'destinatar_localitate',
					width : 80
				}, {
					name : 'destinatar',
					index : 'destinatar',
					width : 100
				}, {
					name : 'expeditii',
					index : 'expeditii',
					width : 40					
				}, {
					name : 'greutate',
					index : 'greutate',
					align : 'right',
					width : 50
				}, {
					name : 'plicuri',
					index : 'plicuri',
					align : 'right',
					width : 40
				}, {
					name : 'colete',
					index : 'colete',
					align : 'right',
					width : 40
				}, {
					name : 'paleti',
					index : 'paleti',
					align : 'right',
					width : 40
				}, {
					name : 'km',
					index : 'km',
					align : 'right',
					width : 40
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 600,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'expeditor',
				viewrecords : true,
				sortorder : "ASC",
				pager : '#paginatie_detalii',
				caption : "",
				multiselect : true,				
				subGrid: true,
				beforeSelectRow: function (rowid, e) {
					var $myGrid = $(this),
									i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
									cm = $myGrid.jqGrid('getGridParam', 'colModel');
					return (cm[i].name === 'cb');
				},
				subGridRowExpanded: function(subgrid_id, row_id) {
					var subgrid_table_id, pager_id;
					subgrid_table_id = subgrid_id+"_t";
					pager_id = "p_"+subgrid_table_id;
					$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");
					
					var g_centre = jQuery('#liste_centre');
					var g_sel_id = g_centre.jqGrid('getGridParam', 'selrow');

					var cond = '';
					var centru = g_sel_id;
					var data_start = $('#data_start').val();
					var data_final = $('#data_final').val();
					cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
					
					jQuery("#"+subgrid_table_id).jqGrid({
						url : HTTP + 'decont_ramburs/json/expeditii?expeditor='+row_id+cond,
						datatype : "json",
						colNames : ['Expeditie','Destinatar','Localitate', 'Greutate','Plicuri','Colete','Paleti','Km'],
						colModel : [ {
							name : 'expeditie',
							index : 'expeditie',
							width : 80					
						}, {
							name : 'destinatar',
							index : 'destinatar',
							width : 110
						}, {
							name : 'destinatar_localitate',
							index : 'destinatar_localitate',
							width : 120
						}, {
							name : 'greutate',
							index : 'greutate',
							align : 'right',
							width : 50
						}, {
							name : 'plicuri',
							index : 'plicuri',
							align : 'right',
							width : 40
						}, {
							name : 'colete',
							index : 'colete',
							align : 'right',
							width : 40
						}, {
							name : 'paleti',
							index : 'paleti',
							align : 'right',
							width : 40
						}, {
							name : 'km',
							index : 'km',
							align : 'right',
							width : 40
						} ],
						rowNum : 20,
					   	pager: pager_id,
					   	sortname: 'expeditor',
					    sortorder: "asc",
					    height: '100%',
					    ondblClickRow : function(id) {
								AfisareDetaliiExpeditieCuID(id)
						},
					});
					jQuery("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
				},
				subGridRowColapsed: function(subgrid_id, row_id) {
					// this function is called before removing the data
				}
			});
      grid.jqGrid('bindKeys');
}

function AfisareDecontRamburs() {
	var cond = '';
	var data_start = $('#data_start').val();
  var data_final = $('#data_final').val();
  var centru = parseInt($('#centru').val());
	
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	
	if(!isNaN(centru)) cond += '&centru='+centru;

	jQuery("#lista_centre").jqGrid('setGridParam', {
		url : HTTP + 'decont_ramburs/json/centre?a' + cond
	}).trigger("reloadGrid");
}

function openTabDecontRambursDetaliiCentru(id) {
		jQuery("#detalii_centru").jqGrid("clearGridData");
		$('.tab_detalii a').html( client + '&nbsp;&nbsp;&nbsp;&nbsp;<span id="close_tab_but" onclick="inchideDetaliiCentru()">x</span>');
		$('.tab_detalii').show();
}

function inchideDetaliiCentru(){
	jQuery("#detalii_centru").jqGrid("clearGridData");
	$('.tab_detalii').hide();
}