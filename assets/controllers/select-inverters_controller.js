import { Controller } from '@hotwired/stimulus';
import { useDispatch } from 'stimulus-use';
import { Reveal } from 'foundation-sites';
import $ from 'jquery';

export default class extends Controller {

    connect() {
        useDispatch(this);
    }

    checkTrafo({ params: { first, last, trafo }}){
        let body = $('#inverters');
        let checked = $("#trafo" + trafo).prop('checked');

        body.find('input:checkbox[class=js-checkbox]').each(function (){
            if ($(this).prop('id').substring(2) >= first) {
                if ($(this).prop('id').substring(2) <= last){
                    if (checked) $(this).prop('checked', true);
                    else $(this).prop('checked', false);
                }
            }
        });

        $(this.switchTarget).prop('checked', false)
        this.saveCheck();
    }

    checkSelect(){
        let body = $('#inverters');

        $(this.switchTarget).prop('checked', false)

    }

    selectAll(){
        let body = $('#inverters');

        if ($(this.switchTarget).prop('checked')) {

            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', true);
            });
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', true);
            });

            inverterString = '*';
            inverterNameString = '*';
        } else {
            body.find('input:checkbox[class=js-checkbox-trafo]').each(function () {
                $(this).prop('checked', false);
            });
            body.find('input:checkbox[class=js-checkbox]').each(function () {
                $(this).prop('checked', false);
            });
        }

    }

    saveInverters(){

        let body = $('#inverters');
        let invids = $('#invids');
        let invnames = $("#invnames");

        let chekedInverters = '';
        let chekedInverterIds = '';
        let temp= '';

        console.log(' a b    c d e   f g   '.replaceAll(' ',''));

        body.find('input:checkbox[class=js-checkbox]').each(function () {
            if($(this).prop('checked')){
                chekedInverters = chekedInverters+$(this).attr('name')+',';
                temp = $(this).attr('name').replaceAll(' ','')
                temp = temp.replaceAll('.','_')
                chekedInverterIds = chekedInverterIds+$("#"+temp).val()+',';
            }

        });
        chekedInverters = chekedInverters.slice(0, -1);
        chekedInverterIds = chekedInverterIds.slice(0, -1);

        invnames.prop('value', chekedInverters);
        invids.prop('value', chekedInverterIds);

        $("#mysubmit").val('yes');
        $("#chart-control").delay(100).submit()
    }
}
