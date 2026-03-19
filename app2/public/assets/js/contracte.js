/**
 * Created by ionut on 01/09/2017.
 */
$(document).ready(function()
{


    $( "#data_contract" ).change(function() {
        var data_selectata = $( "#data_contract" ).val().split('.');
        var date_sel = new Date(data_selectata[2]+"-"+data_selectata[1]+"-" + data_selectata[0]);
        var now = new Date();
        var an = date_sel.getFullYear() - 2000 ;
        var luna = pad(date_sel.getMonth());
        var zi = pad(date_sel.getDate());
        $('#nr_contract').val(an + '' + luna + '' + zi + '' + pad(now.getHours()) + '' + pad(now.getMinutes()));
        // $('#nr_contract').val(date_sel.getYear() + '' + date_sel.getMonth() + '' + date_sel.getDate()+ '' + now.getHours()+ '' + now.getMinutes());
    });

    $( "#cod_fiscal" )
        .focusout(function() {
            $('#cod_fiscal').removeClass('cod-valid');
            $('#cod_fiscal').removeClass('cod-invalid');
            if(!isValidCui($( "#cod_fiscal" ).val())){
                $('#cod_fiscal').addClass('cod-invalid');
                return;
            }
            $.ajax( {type: "POST", url : HTTP + 'facturi/verificare_cui', dataType: 'json', data : {cui:$(this).val()}} )
                .done(function(res) {
                    if(res.denumire.length){
                        // console.log(res);
                        // $('#detalii_societate').html('<label>Nume:</label><strong>'+res.denumire + '</strong><br><label>Adresa:</label>'+res.adresa + '<br><label>Mesaj:</label>'+res.mesaj);
                        $('#cod_fiscal').val($('#cod_fiscal').val().toUpperCase());
                        $('#cod_fiscal').addClass('cod-valid');
                        $('#nume_societate').val(res.denumire);
                        $('#sediu_social').val(res.adresa);
                        // $('#info_anaf').html( 'A.N.A.F.:<br>Nume:'+ res.denumire + '<br>Adresa:' +res.adresa + '<br>Mesaj:' + res.mesaj);
                        if(res.tva){
                            $('#cod_fiscal').val('RO'+res.cui);
                        } else {
                            $('#cod_fiscal').val(res.cui);
                        }
                    } else {
                        $('#COD_FISCAL').addClass('cod-invalid');
                    }
                })
                .fail(function(res) {

                })
                .always(function() {

                });
        });

    $('.datepicker').datepicker({
        format: 'dd.mm.yyyy'
    });
});

var nr_contacte = 0;

function AdaugaContact(){
    if($("#contact_nume").val().length < 3)
        return;
    var contact = '<li id="persoana_contact_'+nr_contacte+'"><input name="contact['+ nr_contacte +'][nume]" value="'+ $("#contact_nume").val() + '" readonly="readonly" class="form-control" style="width:250px;">';
    contact += '<input name="contact['+ nr_contacte +'][email]" value="'+ $("#contact_email").val() + '" readonly="readonly" class="form-control" style="width:250px;">';
    contact += '<input name="contact['+ nr_contacte +'][telefon]" value="'+ $("#contact_telefon").val() + '" readonly="readonly" class="form-control" style="width:250px;"><a href="#" class="remove-but" onclick="removeContact('+nr_contacte+');return false;"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></a></li>';
    $('#lista_persoane_contact').append(contact);
    nr_contacte++;
    $('#contact_nume,#contact_email,#contact_telefon').val('');
}

var nr_pc = 0;

function AdaugaPunctDeLucru(){
    if($("#pc_nume").val().length < 3)
        return;
    var pc = '<li id="punct_de_lucru'+nr_pc+'"><input name="pc['+ nr_pc +'][pc_nume]" value="'+ $("#pc_nume").val() + '" readonly="readonly" class="form-control" style="width:250px;">';
    pc += '<input name="pc['+ nr_pc +'][pc_adresa]" value="'+ $("#pc_adresa").val() + '" readonly="readonly" class="form-control" style="width:250px;">';
    pc += '<input name="pc['+ nr_pc +'][pc_telefon]" value="'+ $("#pc_telefon").val() + '" readonly="readonly" class="form-control" style="width:250px;"><a href="#" class="remove-but" onclick="removePunctDeLucru('+nr_pc+');return false;"><span class="glyphicon glyphicon-minus" aria-hidden="true"></span></a></li>';
    $('#lista_puncte_de_lucru').append(pc);
    nr_pc++;
    $('#pc_nume,#pc_adresa,#pc_telefon').val('');
}

function pad(d) {
    return (d < 10) ? '0' + d.toString() : d.toString();
}

function removeContact(nr) {
    $('#persoana_contact_'+nr).remove();
}

function removePunctDeLucru(nr) {
    $('#punct_de_lucru'+nr).remove();
}