import {Controller} from "@hotwired/stimulus";
import {useDebounce} from "stimulus-use";


export default class  extends Controller{

    static  values={
        url: String
    };

    static targets=['bodytab','val'];

    static  debounces=['searchQuery']

    connect() {
       useDebounce(this);
    }

    onSearchGroup(event) {

        this.searchQuery(event.currentTarget.value);
    }

    async searchQuery(query){

        this.valTarget.value=query;

        const  params = new URLSearchParams({
            q:query,
            search:1
        });

        const response= await  fetch(`${this.urlValue}?${params.toString()}`);
        this.bodytabTarget.innerHTML=await response.text();

        console.log(this.valTarget.value);
    }
}