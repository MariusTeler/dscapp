function Grid_FinanciarCentru_DashboardRbs(){
		var grid = $("#financiar_centru_dash_rbs");
		grid.jqGrid({
			url : "/financiar/centru/dash_rbs_cash_cont",
			datatype : "json",
			colModel : [ {
				label : 'Ce.',
				name : 'centru',
                search: false,
                sortable: false,
				align: 'center',
                width: 50,
			},{
				label : 'Nedecontate',
				name : 'rbs_nedecontate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'Decontate',
				name : 'rbs_decontate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'Aprobate',
				name : 'rbs_aprobate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'Generate',
				name : 'rbs_generate',
                search: false,
                sortable: false,
				align: 'right',
			},{
                label : 'Generare',
                name : 'generare_rbs',
                formatter : function(cellvalue, options, rowObject) {
                    if (cellvalue) {
                        return '<input type="button" value="Genereaza"  onclick="CreareRbsCash('+cellvalue+');" />';
                    } else {
                        return '';
                    }
                },
                search: false,
                sortable: false,
                align: 'center',
                with: 80,
            },{
                label : 'Print',
                name : 'printare_rbs',
                formatter : function(cellvalue, options, rowObject) {
                    if (cellvalue) {
                        return '<input type="button" value="Print"  onclick="PrintareRbsCash('+cellvalue+');" />';
                    } else {
                        return '';
                    }
                },
                search: false,
                sortable: false,
                align: 'center',
                width: 70,
            },{
                label : 'CASA RBS',
                name : 'casa_rbs',
                search: false,
                sortable: false,
                align: 'right',
            },{
                label : 'Transfer',
                name : 'transfer_casa_rbs',
                search: false,
                sortable: false,
                align: 'center',
            }],
	        gridComplete: function() {
	            let cRows = this.rows.length, iRow, row, className, centru_id, centru_name, centru_master_id;

	            for (iRow=1; iRow < cRows; iRow++) {
	                row = this.rows[iRow];
                    centru_id = row.id;
                    centru_nume = $(row.cells[0]).html();
                    centru_master_id = $(row.cells[8]).html();
                    if(centru_id == centru_master_id){
                        $(row.cells[8]).html('');
                        continue;
                    }
                    $(row.cells[8]).html('<input type="button" value="Transfera" onclick="TransferCasa(1, ' + centru_id + ', \'' + centru_nume + '\');" />');
	            }
	        },
			autowidth : true,
			scroll : true,
			rowNum : 100,
			mtype : "GET",
			shrinkToFit: true,
			forceFit: true,
			height:150,
			caption : 'Situatie RBS CASH/CONT',
			multiselect : false,
			footerrow: true,
			userDataOnFooter: true,
		})
	}

    function Grid_FinanciarCentru_DashboardFaCh(){
		var grid = $("#financiar_centru_dash_fa_ch");
		grid.jqGrid({
			url : "/financiar/centru/dash_fa_ch",
			datatype : "json",
			colModel : [ {
				label : 'Ce.',
				name : 'centru',
                search: false,
                sortable: false,
				align: 'center',
                width: 60,
			},{
				label : 'Fa. Nedecontate',
				name : 'transport_nedecontate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'Fa. Decontate',
				name : 'facturi_decontate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'Ch. Nedecontate',
				name : 'chitante_nedecontate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'Ch. Decontate',
				name : 'chitante_decontate',
                search: false,
                sortable: false,
				align: 'right',
			},{
				label : 'CASA FA/CH',
				name : 'casa_fa_ch',
                search: false,
                sortable: false,
				align: 'right',
			},{
                label : 'Transfer',
                name : 'transfer_casa_fa_ch',
                search: false,
                sortable: false,
                align: 'center',
                with: 80,
            }],
	        gridComplete: function() {
	            let cRows = this.rows.length, iRow, row, className, centru_id, centru_name, centru_master_id;

	            for (iRow=1; iRow < cRows; iRow++) {
	                row = this.rows[iRow];
                    centru_id = row.id;
                    centru_nume = $(row.cells[0]).html();
                    centru_master_id = $(row.cells[6]).html();
                    if(centru_id == centru_master_id){
                        $(row.cells[6]).html('');
                        continue;
                    }
                    $(row.cells[6]).html('<input type="button" value="Transfera" onclick="TransferCasa(2, ' + centru_id + ', \'' + centru_nume + '\');" />');
	            }
	        },
			autowidth : true,
			scroll : true,
			rowNum : 100,
			mtype : "GET",
			shrinkToFit: true,
			forceFit: true,
			height:150,
			caption : 'Situatie Facturi/Chitante/Cheltuieli CASH',
			multiselect : false,
			footerrow: true,
			userDataOnFooter: true,
		})
	}

	function Grid_FinanciarCentru_RbsCashDecontate(){
		var grid = $("#financiar_centru_rbs_cash_cont_decontate");
		grid.jqGrid({
			url : "/financiar/centru/rbs_cash_cont_decontate",
			datatype : "json",
			colModel : [ {
				label : 'AWB',
				name : 'awb',
				index : 'ep.expeditie',
				align: 'left',
			},{
				label : 'Ch. RBS',
				name : 'ch_ramburs',
				index : 'dr.ch_ramburs',
				search: false,
				sortable: true,
				align: 'left',
			},{
				label : 'Valoare',
				name : 'valoare',
				search: false,
				sortable:false,
				align: 'right',
			},{
				label : 'Tip plata',
				name : 'tip_plata',
				index : 'ep.tip_plata',
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
			},{
                label : 'Data inc.',
                name : 'dr.dataInc',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d H:i:s',
                    newformat : 'd.m.Y H:i'
                },
                align: 'center'
			},{
				label : 'Expeditor',
				name : 'expeditor',
				index : 'cle.nume',
			},{
				label : 'Agent',
				name : 'agent',
				index : 'ag.nume_ag',
			}],
			autowidth : true,
			width : 960,
			scroll : true,
			rowNum : 100,
			mtype : "GET",
			shrinkToFit: true,
			forceFit: true,
			height:200,
			rownumbers : true,
			viewrecords : true,
			caption : 'Lista Decontate RBS CASH/CONT',
			multiselect : false,
			footerrow: true,
			userDataOnFooter: true,
			ondblClickRow : function(rowid) {
				let awb = grid.jqGrid('getCell', rowid, 'awb');
				console.log("Afisare detalii expeditie cu AWB: " + awb);
				AfisareDetaliiExpeditieCuID(awb);
			}
		})
		.jqGrid('bindKeys')
		.jqGrid('filterToolbar',{
			stringResult : true,
			searchOnEnter : false
		});
	}

	function Grid_FinanciarCentru_RbsCashAprobate(){
		var grid = $("#financiar_centru_rbs_cash_aprobate");
		grid.jqGrid({
			url : "/financiar/centru/rbs_cash_aprobate",
			datatype : "json",
			colModel : [{
				label : 'AWB',
				name : 'awb',
				index : 'ep.expeditie',
				align: 'left',
			},{
                label : 'Data awb',
                name : 'ep.data_expeditie',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d',
                    newformat : 'd.m.Y'
                },
                align: 'center'
			},{
				label : 'Expeditor',
				name : 'expeditor',
				index : 'cle.nume',
			},{
				label : 'CUI',
				name : 'cui',
				index : 'cod_fiscal',
			},{
				label : 'Localitate',
				name : 'localitate',
				index : 'lce.nume_lc',
			},{
				label : 'Valoare RBS',
				name : 'valoare',
				search: false,
				sortable:false,
				align: 'right',
			},{
				label : 'Tip plata',
				name : 'tip_plata',
				index : 'ep.tip_plata',
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
			}],
			autowidth : true,
			width : 960,
			scroll : true,
			rowNum : 100,
			mtype : "GET",
			shrinkToFit: true,
			forceFit: true,
			height:200,
			rownumbers : true,
			viewrecords : true,
			caption : 'Lista Aprobate RBS CASH',
			multiselect : false,
			footerrow: true,
			userDataOnFooter: true,
			ondblClickRow : function(rowid) {
				let awb = grid.jqGrid('getCell', rowid, 'awb');
				console.log("Afisare detalii expeditie cu AWB: " + awb);
				AfisareDetaliiExpeditieCuID(awb);
			}
		})
		.jqGrid('bindKeys')
		.jqGrid('filterToolbar',{
			stringResult : true,
			searchOnEnter : false
		});
	}

	function Grid_FinanciarCentru_CashNedecontate(){
    	var grid = $("#financiar_centru_cash_nedecontate");
		grid.jqGrid({
			url : "/financiar/centru/cash_nedecontate",
			datatype : "json",
            colModel : [ {
                label : 'Fct./Chit.',
                name : 'fctChit',
				index : 'fctChit',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Valoare',
                name : 'valoare',
				index : 'valoare',
                search: false,
                sortable:true,
                align: 'right',
            },{
                label : 'Awb/Client',
                name : 'awbs',
				index : 'awbs',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Tip',
                name : 'tip',
                index : 'tip',
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
            },{
                label : 'Operatiune',
                name : 'operatiune',
                index : 'operatiune',
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
				index : 'dataInc',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d H:i:s',
                    newformat : 'd.m.Y H:i'
                },
                align: 'center'
		    },{
				label : 'Agent',
				name : 'agent',
				index : 'agent',
				sortable : true,
			}],
            autowidth : true,
            width : 960,
            scroll : false,
        	rowNum : 5000,
            mtype : "GET",
            shrinkToFit: true,
            forceFit: true,
            height:250,
            rownumbers : true,
            viewrecords : true,
            caption : 'Lista Nedecontate CASH (transport / ramburs / facturi client CTR)',
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

	function Grid_FinanciarCentru_FacturiDecontateCash(){
    	var grid = $("#financiar_centru_facturi_cash_decontate");
		grid.jqGrid({
			url : "/financiar/centru/facturi_cash_decontate",
			datatype : "json",
            colModel : [ {
                label : 'Factura',
                name : 'serie',
				index : 'df.serie',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Valoare',
				name : 'valoare',
                index : 'df.suma',
                search: false,
                sortable:true,
                align: 'right',
            },{
                label : 'Awb',
                name : 'awbs',
                search: false,
                sortable:false,
                align: 'left',
            },{
                label : 'Operatiune',
                name : 'operatiune',
                index : 'df.operatiune',
                formatter: 'select',
                edittype:'select',
                editoptions : {
                    value : "1:Colectare;2:Livrare",
                },
                stype : 'select',
                searchoptions : {
                    value : ":Toate;1:Colectare;2:Livrare",
                    sopt : [ 'eq' ]
                },
                align: 'center', 
            }, {
                label : 'Data inc.',
                name : 'dataInc',
				index : 'df.data',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d H:i:s',
                    newformat : 'd.m.Y H:i'
                },
                align: 'center'
		    },{
				label : 'Agent',
				name : 'agent',
				sortable : true,
				index : 'ag.nume_ag',
			}],
            autowidth : true,
            width : 960,
            scroll : true,
        	rowNum : 100,
            mtype : "GET",
            shrinkToFit: true,
            forceFit: true,
            height:150,
            rownumbers : true,
            viewrecords : true,
            caption : 'Lista Decontate Facturi transport CASH',
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

	function Grid_FinanciarCentru_ClientCtrDecontateCash(){
    	var grid = $("#financiar_centru_client_ctr_cash_decontate");
		grid.jqGrid({
			url : "/financiar/centru/client_ctr_cash_decontate",
			datatype : "json",
            colModel : [ {
                label : 'Factura',
                name : 'serie',
				index : 'cf.ch_bon',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Valoare',
				name : 'valoare',
                index : 'cf.suma',
                search: false,
                sortable:true,
                align: 'right',
            },{
                label : 'Client',
                name : 'client',
                search: false,
                sortable:false,
                align: 'left',
            },{
                label : 'Data inc.',
                name : 'dataInc',
				index : 'cf.dataInc',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d H:i:s',
                    newformat : 'd.m.Y H:i'
                },
                align: 'center'
		    },{
				label : 'Agent',
				name : 'agent',
				sortable : true,
				index : 'ag.nume_ag',
			}],
            autowidth : true,
            width : 960,
            scroll : true,
        	rowNum : 100,
            mtype : "GET",
            shrinkToFit: true,
            forceFit: true,
            height:150,
            rownumbers : true,
            viewrecords : true,
            caption : 'Lista Decontate Chitante client CTR CASH',
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

	function Grid_FinanciarCentru_IncasariCard(){
    	var grid = $("#financiar_centru_incasari_card");
		grid.jqGrid({
			url : "/financiar/centru/incasari_card",
			datatype : "json",
            colModel : [ {
                label : 'Fct./Chit.',
                name : 'fctChit',
				index : 'fctChit',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Transaction ID',
                name : 'transactionId',
				index : 'transactionId',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Valoare',
                name : 'valoare',
				index : 'valoare',
                search: false,
                sortable:true,
                align: 'right',
            },{
                label : 'Awb/Client',
                name : 'awbs',
				index : 'awbs',
                search: false,
                sortable:true,
                align: 'left',
            },{
                label : 'Tip',
                name : 'tip',
                index : 'tip',
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
            },{
                label : 'Operatiune',
                name : 'operatiune',
                index : 'operatiune',
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
				index : 'dataInc',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d H:i:s',
                    newformat : 'd.m.Y H:i'
                },
                align: 'center'
		    },{
				label : 'Agent',
				name : 'agent',
				index : 'agent',
				sortable : true,
			}],
            autowidth : true,
            width : 960,
            scroll : false,
        	rowNum : 5000,
            mtype : "GET",
            shrinkToFit: true,
            forceFit: true,
            height:150,
            rownumbers : true,
            viewrecords : true,
            caption : 'Lista incasari CARD (transport / ramburs / facturi client CTR)',
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

    function Grid_FinanciarCentru_IstoricCentru() {
        var grid = $("#financiar_centru_istoric");
		grid.jqGrid({
			url : "/financiar/centru/istoric",
			datatype : "json",
            colNames : ['Data decont', 'Fa. decontate', 'Ch. decontate', 'RBS decontate', 
                'Chelt. RBS', 'Chelt. Fa/Ch', 'Nr. RBS generate', 'RBS generate', 
                'Sal. confirmate', 'Casa RBS', 'Casa Fa/Ch', 'Trez. RBS', 'Trez. Fa/Ch', 'Print RBS'],
            colModel : [ {
                name : 'data_decont',
                search : false,
				sortable : false,
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d',
                    newformat : 'd.m.Y'
                },
                align: 'center',
            },{
                search : false,
				sortable : false,
                name : 'total_facturi_decontate',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'total_chitante_decontate',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'total_rbs_cash_cont_decontate',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'total_cheltuieli_rbs',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'total_cheltuieli_fa_ch',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'nr_rbs_cash_generate',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'total_rbs_cash_generate',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'total_salarii_confirmate',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'casa_rbs_cash_cont',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'casa_rbs_fa_ch',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'trezorerie_rbs',
                align: 'right',
            },{
                search : false,
				sortable : false,
                name : 'trezorerie_fa_ch',
                align: 'right',
            },{
                search : false, 
                sortable : false,
                name : 'print_rbs',
                formatter : function(cellvalue, options, rowObject) {
                    if (cellvalue) {
                        return '<a href="javascript:void(0)" onclick="PrintareRbsCash('+ cellvalue.centru_id +', \'' + cellvalue.created_at + '\');">Print</a>';
                    } else {
                        return '';
                    }
                },
                align: 'center',
            }],
            caption : 'Lista Istoric centru',
            scroll : true,
        	rowNum : 100,
            height: 300,
            autowidth : true,
			width : 960,
            shrinkToFit: true,
            forceFit: true,
            rownumbers : false,
            gridview : false,
            pager : false,
            sortname: 'created_at',
			sortorder: "desc",
            filterToolbar : {},
            viewrecords : false
        })
        .jqGrid('bindKeys');
    }

    function CreareRbsCash(centru_id) {
        $.ajax({
            url: '/financiar/centru/creare_rbs_cash',
            data: {
                centru_id: centru_id
		    },
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#valoare_rbs_cash_cont_banca').text(response.casa_rbs);
                    jQuery("#financiar_centru_rbs_cash_aprobate").trigger("reloadGrid");
                    jQuery("#financiar_centru_dash_rbs").trigger("reloadGrid");
                    AfiseazaEroare(response.message);
                } else {
                    AfiseazaEroare('Eroare la generarea RBS cash: <br/>' + response.message);
                }
            },
            error: function(xhr, status, error) {
                AfiseazaEroare('Eroare la comunicarea cu serverul: <br/>' + error);
            }
        });
    }

    function PrintareRbsCash(centru_id, created_at) {
        cond = '&centru_id=' + centru_id;
        if (created_at) {
            cond = "&created_at=" + created_at;
        }
        //console.log("Printare RBS cash pentru data: " + created_at);
        $.download('/financiar/centru/printare_rbs_cash', cond, false);
    }

    function TransferCasa(tip, centru_id, centru_nume) {
        $('#confirmare').html('<p>Confirmi ca ai primit banii de la centrul '+centru_nume+' ? </p>');
        $("#confirmare").dialog("destroy");
		$("#confirmare").dialog({
			width : 400,
			height : 210,
			draggable : false,
			resizable : false,
			modal: true,
			buttons: {
				"DA": function() {
					$(this).dialog("close");
                    $.ajax({
                        url: '/financiar/centru/transfer_casa',
                        type: 'POST',
                        data: {
                            tip: tip,
                            centru_id: centru_id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                jQuery("#financiar_centru_dash_rbs").trigger("reloadGrid");
                                jQuery("#financiar_centru_dash_fa_ch").trigger("reloadGrid");
                                jQuery("#financiar_centru_istoric").trigger("reloadGrid");
                                $('#valoare_rbs_cash_cont_banca').text(response.casa_rbs);
                                $('#valoare_fa_ch_cash_cont').text(response.casa_fa_ch);
                                AfiseazaEroare(response.message);
                            } else {
                                AfiseazaEroare('Eroare la transfer bani de RBS: <br/>' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            AfiseazaEroare('Eroare la comunicarea cu serverul: <br/>' + error);
                        }
                    });
                },
				'Nu': function() {
					$(this).dialog("close");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		});
    }

    function IncarcareCasa(tip) {
        const numberRegex = /^\d+(\.\d{1,2})?$/;

        $("#incarcare_dialog").dialog("destroy");
        $('#incarcare_amount').val('');
        $('#incarcare_descriere').val('');
        $('#incarcare_amount').removeClass('ui-state-error');
        $('#incarcare_descriere').removeClass('ui-state-error');

        if(tip == 1){
            $('#tip_incarcare').html('Incarcare CASA RBS');
        } else if(tip == 2){
            $('#tip_incarcare').html('Incarcare CASA FA/CH');
        } else {
            $('#tip_incarcare').html('');
            AfiseazaEroare('Eroare la incarcare casa: <br/>Tip invalid!');
            return;
        }

		$("#incarcare_dialog").dialog({
			height : 330,
			width : 330,
			modal : true,
            draggable : false,
			resizable : false,
			buttons : {
				"Adauga": function() {
                    //test amount regex int or float with dot 2 decimals
                
                    if (!numberRegex.test($('#incarcare_amount').val()) || $('#incarcare_amount').val() <= 0) {
                        $('#incarcare_amount').addClass('ui-state-error');
                        return;
                    } else {
                        $('#incarcare_amount').removeClass('ui-state-error');
                    }

                    if($('#incarcare_descriere').val().length < 3){
                        $('#incarcare_descriere').addClass('ui-state-error');
                        return;
                    } else {
                        $('#incarcare_descriere').removeClass('ui-state-error');
                    }

                    $(this).dialog("close");
                    $.ajax({
                        url: '/financiar/centru/incarcare_casa',
                        type: 'POST',
                        data: {
                            tip: tip,
                            amount: $('#incarcare_amount').val(),
                            descriere: $('#incarcare_descriere').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                jQuery("#financiar_centru_dash_rbs").trigger("reloadGrid");
                                $('#valoare_rbs_cash_cont_banca').text(response.casa_rbs);
                                jQuery("#financiar_centru_dash_fa_ch").trigger("reloadGrid");
                                $('#valoare_fa_ch_cash_cont').text(response.casa_fa_ch);
                                jQuery("#financiar_centru_istoric").trigger("reloadGrid");
                                AfiseazaEroare(response.message);
                            } else {
                                AfiseazaEroare('Eroare la incarcare casa : <br/>' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            AfiseazaEroare('Eroare la comunicarea cu serverul: <br/>' + error);
                        }
                    });
                },
				Cancel: function() {
					$(this).dialog("close");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		})
        .dialog('option', 'title', 'Incarcare CASA');
    }

    function IncarcareTrezorerie(tip) {
        const numberRegex = /^\d+(\.\d{1,2})?$/;

        $("#incarcare_dialog").dialog("destroy");
        $('#incarcare_amount').val('');
        $('#incarcare_descriere').val('');
        $('#incarcare_amount').removeClass('ui-state-error');
        $('#incarcare_descriere').removeClass('ui-state-error');

        if(tip == 3){
            $('#tip_incarcare').html('Incarcare Trezorerie RBS');
        } else if(tip == 4){
            $('#tip_incarcare').html('Incarcare Trezorerie FA/CH');
        } else {
            $('#tip_incarcare').html('');
            AfiseazaEroare('Eroare la incarcare trezorerie: <br/>Tip invalid!');
            return;
        }

		$("#incarcare_dialog").dialog({
			height : 330,
			width : 330,
			modal : true,
            draggable : false,
			resizable : false,
			buttons : {
				"Adauga": function() {
                    //test amount regex int or float with dot 2 decimals
                
                    if (!numberRegex.test($('#incarcare_amount').val()) || $('#incarcare_amount').val() <= 0) {
                        $('#incarcare_amount').addClass('ui-state-error');
                        return;
                    } else {
                        $('#incarcare_amount').removeClass('ui-state-error');
                    }

                    if($('#incarcare_descriere').val().length < 3){
                        $('#incarcare_descriere').addClass('ui-state-error');
                        return;
                    } else {
                        $('#incarcare_descriere').removeClass('ui-state-error');
                    }

                    $(this).dialog("close");
                    $.ajax({
                        url: '/financiar/centru/incarcare_trezorerie',
                        type: 'POST',
                        data: {
                            tip: tip,
                            amount: $('#incarcare_amount').val(),
                            descriere: $('#incarcare_descriere').val()
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                jQuery("#financiar_centru_dash_rbs").trigger("reloadGrid");
                                $('#valoare_rbs_cash_cont_banca').text(response.casa_rbs);
                                jQuery("#financiar_centru_dash_fa_ch").trigger("reloadGrid");
                                $('#valoare_fa_ch_cash_cont').text(response.casa_fa_ch);
                                jQuery("#financiar_centru_istoric").trigger("reloadGrid");
                                AfiseazaEroare(response.message);
                            } else {
                                AfiseazaEroare('Eroare la incarcare trezorerie : <br/>' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            AfiseazaEroare('Eroare la comunicarea cu serverul: <br/>' + error);
                        }
                    });
                },
				Cancel: function() {
					$(this).dialog("close");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		})
        .dialog('option', 'title', 'Incarcare Trezorerie');
    }

    function ConfirmaSalarii() {
        $.ajax({
            url: '/financiar/centru/confirma_salarii',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#valoare_salarii').text(response.salarii);
                    $('#valoare_rbs_cash_cont_banca').text(response.casa_rbs);
                    AfiseazaEroare('Salariile au fost confirmate cu succes!');
                } else {
                    AfiseazaEroare('Eroare la confirmarea salariilor: <br/>' + response.message);
                }
            },
            error: function(xhr, status, error) {
                AfiseazaEroare('Eroare la comunicarea cu serverul: <br/>' + error);
            }
        });
    }

    function DashboardFinanciarCentre(){
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		let cond = 'data_start=' + data_start + '&data_final='+ data_final;

		window.open('/financiar/centre?'+cond,'_self');
	}

	function Grid_FinanciarCentre_Centre() {
		var grid = jQuery('#financiar_centre_centre');
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		let cond = 'data_start=' + data_start + '&data_final='+ data_final;

		grid.jqGrid(
		{
			url : '/financiar/centre/dashboard?'+cond,
			datatype: 'json',
			mtype: 'GET',
			colNames : [ 'Centru', 'Cod', 'Financiar ce.', 'Dispecerat', 'Nr. fact. inc.', 'Total fact. inc.', 'Nr. chit. inc.', 'Total chit. inc.', 
				'Nr. RBS cash/cont inc.', 'Total RBS cash/cont inc.', 'Total facturi decontate', 'Total chitante decontate', 
				'Total RBS cash/cont decontate', 'Total chelt. RBS', 'Total chelt. Fa/Ch',
				'Nr. RBS cash aprobate', 'Total RBS cash aprobate', 'Nr. RBS cash generate', 'Total RBS cash generate',
				'Total Salarii aprobate', 'Total Salarii confirmate', 'Total Casa RBS', 'Total Casa Fa/Ch', 'Total Trez. RBS', 'Total Trez. Fa/Ch' ],
			colModel : [ {
					name : 'centru_nume',
                    search : false,
                    sortable : false,
				},{
					name : 'cod',
					align: 'center',
                    search : false,
                    sortable : false,
                },{
                    name : 'financiar_nume',
                    search : false,
                    sortable : false,
				},{
					name : 'dispecerat',
                    search : false,
                    sortable : false,
				},{
					name : 'nr_facturi_incasate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_facturi_incasate',
                    search : false,
                    sortable : false,
				},{
					name : 'nr_chitante_incasate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_chitante_incasate',
                    search : false,
                    sortable : false,
				},{
					name : 'nr_rbs_cash_cont_incasate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_rbs_cash_cont_incasate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_facturi_decontate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_chitante_decontate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_rbs_cash_cont_decontate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_cheltuieli_rbs',
                    search : false,
                    sortable : false,
				},{
					name : 'total_cheltuieli_fa_ch',
                    search : false,
                    sortable : false,
				},{
					name : 'nr_rbs_cash_aprobate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_rbs_cash_aprobate',
                    search : false,
                    sortable : false,
				},{
					name : 'nr_rbs_cash_generate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_rbs_cash_generate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_salarii_aprobate',
                    search : false,
                    sortable : false,
				},{
					name : 'total_salarii_confirmate',
                    search : false,
                    sortable : false,
				},{
					name : 'casa_rbs_cash_cont',
                    search : false,
                    sortable : false,
				},{
                    name : 'casa_rbs_fa_ch',
                    search : false,
                    sortable : false,
                }, {
                    name : 'trezorerie_rbs',
                    search : false,
                    sortable : false,
                }, {
                    name : 'trezorerie_fa_ch',
                    search : false,
                    sortable : false,
                }
			],
			scroll : false,
        	rowNum : 150,
            mtype : "GET",
            shrinkToFit: false,
            forceFit: false,
			rownumbers : false,
			width : 970,
			height : 500,
			viewrecords : true,
			pager : false,
			filterToolbar : {},
			caption:'Situatie financiara centre',
			subGrid: true,
			subGridRowExpanded: function(subgrid_id, row_id) {
				var subgrid_table_id, pager_id;
				subgrid_table_id = subgrid_id+"_t";
				pager_id = "p_"+subgrid_table_id;
				$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

				jQuery("#"+subgrid_table_id).jqGrid({
					url : '/financiar/centre/dashboard/centru?centru_id='+row_id+ '&'+cond,
							datatype : "json",
							colNames : ['Data decont', 'Total facturi decontate', 'Total chitante decontate', 'Total RBS cash/cont decontate', 
								'Total chelt. RBS', 'Total chelt. Fa/CH', 'Nr. RBS cash generate', 'Total RBS cash generate', 
								'Total salarii confirmate', 'Total Casa RBS', 'Total Casa Fa/Ch', 'Total Trez. RBS', 'Total Trez. Fa/Ch' ],
							colModel : [ {
								name : 'data_decont',
								formatter : 'date',
								formatoptions : {
									srcformat : 'Y-m-d',
									newformat : 'd.m.Y'
								},
								align: 'center',
                                search : false,
                                sortable : false,
							},{
								name : 'total_facturi_decontate',
                                search : false,
                                sortable : false,
							},{
								name : 'total_chitante_decontate',
                                search : false,
                                sortable : false,
							},{
								name : 'total_rbs_cash_cont_decontate',
                                search : false,
                                sortable : false,
							},{
								name : 'total_cheltuieli_rbs',
                                search : false,
                                sortable : false,
							},{
								name : 'total_cheltuieli_fa_ch',
                                search : false,
                                sortable : false,
							},{
								name : 'nr_rbs_cash_generate',
                                search : false,
                                sortable : false,
							},{
								name : 'total_rbs_cash_generate',
                                search : false,
                                sortable : false,
							},{
								name : 'total_salarii_confirmate',
                                search : false,
                                sortable : false,
							},{
								name : 'casa_rbs_cash_cont',
                                search : false,
                                sortable : false,
							},{
                                name : 'casa_rbs_fa_ch',
                                search : false,
                                sortable : false,
                            }, {
                                name : 'trezorerie_rbs',
                                search : false,
                                sortable : false,
                            }, {
                                name : 'trezorerie_fa_ch',
                                search : false,
                                sortable : false,
                            }],
                            autowidth : true,
                            width : 900,
                            height : '100%',
							rowNum : 5000,
							height: 100,
							shrinkToFit: false,
							forceFit: false,
							rownumbers : false,
							gridview : false,
							pager : false,
							filterToolbar : {},
							viewrecords : false
				});
			}
		})
		.jqGrid('bindKeys');
	}

    function Grid_FinanciarCentre_Salarii() { 
            $("#centre_salarii_listare").jqGrid({
                url : '/financiar/salarii/centre',
                datatype : "json",
                colNames : [ 'ID', 'Centru', 'Cod', 'Financiar ce.', 'Financiar', 'Nr. agenti', 'Salarii aprobate', 'Data aprobare', 'Salarii confirmate', 'Salarii noi' ],
                colModel : [ {
                    name : 'id',
                    index : 'ce.id',
                    editable : false,
                    search : true,
                    width : 15
                },{
                    name : 'centru_nume',
                    index : 'ce.nume',
                    editable : false,
                    search : true,
                    width : 50
                },{
                    name : 'centru_cod',
                    index : 'ce.label',
                    editable : false,
                    search : true,
                    width : 20,
                    align: 'center',
                },{
                    name : 'master_nume',
                    index : 'mce.nume',
                    editable : false,
                    search : true,
                    width : 50
                },{
                    name : 'financiar',
                    index : 'ce.financiar',
                    search : false,
                    sortable : true,
                    editable : false,
                    edittype:'checkbox',
                    editoptions: { value:"1:0"},
                    formatter: "checkbox",
                    formatoptions: {readonly : true},
                    width : 25,
                    align: 'center'
                },{
                    name : 'nr_agenti',
                    editable : false,
                    search : false,
                    sortable : false,
                    width : 25,
                    align: 'center',
                },{
                    name : 'salarii_aprobate',
                    editable : false,
                    search : false,
                    sortable : false,
                    width : 60
                },{
                    name : 'data_salarii_aprobate',
                    editable : false,
                    search : false,
                    sortable : false,
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center',
                },{
                    name : 'salarii_confirmate',
                    editable : false,
                    search : false,
                    sortable : false,
                    width : 60
                },{
                    name : 'new_salary',
                    editable : true,
                    edittype : "text",
                    editoptions : {
                        dataInit: function (domElem) {
                            setTimeout(function() {
                                $(domElem).focus();
                                $(domElem).setCursorPosition(0);
                            }, 100);
                        },
                    },
                    search : false,
                    sortable : false,
                    width : 60
                },],
                height : 400,
                width : 970,
                scroll : 1,
                rowNum : 100,
                mtype : "GET",
                rownumbers : false,
                gridview : true,
                pager : '#centre_salarii_listare_pag',
                sortname : '2',
                viewrecords : true,
                sortorder : "asc",
                caption : 'Salarii centre',
                cellEdit: true,
                cellsubmit: 'remote',
                cellurl: '/financiar/salarii/edit',
                footerrow: true,
                userDataOnFooter: true,
                afterSubmitCell : function(serverresponse, rowid, cellname, value, iRow, iCol) {
                    var response = jQuery.parseJSON(serverresponse.responseText);
                    if (response.success == 1){
                        //$(this).jqGrid('setCell', rowid, 'new_salary', value, {color:'red', weightfont:'bold'});
                        return [true,""];
                    }
                    else{
                        return [false,response.error];
                    }
                },
            });
            
            $("#centre_salarii_listare").jqGrid('bindKeys');
            $("#centre_salarii_listare").jqGrid('filterToolbar', {
                stringResult : true,
                searchOnEnter : false
            });
        }

    function Grid_FinanciarCentre_Transfer(){
    	var grid = jQuery('#financiar_centre_transfer');
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		let cond = 'data_start=' + data_start + '&data_final='+ data_final;

		grid.jqGrid(
		{
			url : '/financiar/centre/transfer?'+cond,
			datatype : "json",
            colModel : [{
                label : 'Casa',
                name : 'tip_casa',
                index : 'dct.tip',
                formatter: 'select',
                edittype:'select',
                editoptions : {
                    value : "1:RBS;2:FA/CH",
                },
                stype : 'select',
                searchoptions : {
                    value : ":Toate;1:RBS;2:FA/CH",
                    sopt : [ 'eq' ]
                },
                align: 'center', 
            },{
                label : 'Data',
                name : 'created_at',
				index : 'dct.created_at',
                sortable : true,
                search: false,
                sorttype : 'date',
                formatter : 'date',
                formatoptions : {
                    srcformat : 'Y-m-d H:i:s',
                    newformat : 'd.m.Y H:i'
                },
                align: 'center'
		    },{
				label : 'From Centru',
				name : 'from_centru',
                index : 'cef.label',
				align: 'center',
                width: 60,
			},{
				label : 'To Centru',
				name : 'to_centru',
                index : 'cet.label',
				align: 'center',
                width: 60,
			},{
                label : 'Valoare',
				name : 'amount',
                index : 'dct.amount',
                search: false,
                sortable:true,
                align: 'right',
            },{
				label : 'User',
				name : 'user',
				sortable : true,
				index : 'u.user',
                align: 'right',
			}],
            autowidth : true,
            width : 960,
            scroll : true,
        	rowNum : 100,
            mtype : "GET",
            shrinkToFit: true,
            forceFit: true,
            height:300,
            rownumbers : true,
            viewrecords : true,
            sortname : 'dct.created_at',
			sortorder : "desc",
            caption : 'Lista Transferuri Casa intre Centre',
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
