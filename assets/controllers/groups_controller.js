import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static  values={
        url: String
    };

    static targets=['bodytab'];

    connect() {
        console.log('hello');
    }

    onSearchGroup(event) {
        console.log('hello group');
       //  const  params = new URLSearchParams({
       //      q:event.currentTarget.value,
       //      search:1
       //  });
       // const response= await  fetch(`${this.urlValue}?${params.toString()}`);
       // this.bodytabTarget.innerHTML=await response.text();
    }





}
