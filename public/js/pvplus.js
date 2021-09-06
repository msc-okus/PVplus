
/*
addEventListener("load", function () {
    setTimeout(hideURLbar, 0);
}, false);

function hideURLbar() {
    window.scrollTo(0, 1);
}
*/
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
});

$(window).load(function () {
    // Animate loader off screen
    //$(".se-pre-con").fadeOut("slow");
});

$(document).ready(function () {

    let hash = document.location.hash;
    let prefix = "";

    //change hash url on page reload
    if (hash) {
        $('.nav-tabs a[href=\"' + hash.replace(prefix, "") + '\"]').tab('show');
    }

    // change hash url on switch tab
    $('.nav-tabs a').on('shown.bs.tab', function (e) {
        window.location.hash = e.target.hash.replace("#", "#" + prefix);
    });


});

function make_unclickable(link) {
    return false;
}

function open_new_window(link) {
    window.open(link.href, '_blank', '');
    return false;
}

function ask_first(link, question) {
    if (typeof (question) == 'undefined')
        question = 'Are you sure you want to delete?'
    return window.confirm(question);
}

function ConfirmMSGDel(form) {
    var x = confirm("Are you sure you want to delete?");
    console.trace();
    if (x)
        return true;
    // document.getElementById(form).submit();
    else
        return false;
}

function ConfirmMSGSave(form) {
    var x = confirm("Are you sure you want to Save?");
    if (x)
        return true;
    // document.getElementById(form).submit();
    else
        return false;
}

function ConfirmMSLongView() {
    return confirm("This query may take more than 1 minute! Are you sure ?");
}

function ConfirmMSView() {
    return confirm("Are you sure ?");
}

function ConfirmMSDel() {
    return confirm("Are you sure you want to delete?");
}

$(function () {
    var date = new Date();
    var d = new Date();
    d.setDate(date.getDate());
    var bindDatePicker = function() {
        $(".date").datetimepicker({
            format:'YYYY-MM-DD',
            icons: {
                time: "fa fa-clock-o",
                date: "fa fa-calendar",
                up: "fa fa-arrow-up",
                down: "fa fa-arrow-down"
            }
        }).find('input:first').on("blur",function () {
            // check if the date is correct. We can accept dd-mm-yyyy and yyyy-mm-dd.
            // update the format if it's yyyy-mm-dd
            var date = parseDate($(this).val());

            if (! isValidDate(date)) {
                //create date based on momentjs (we have that)
                date = moment().format('YYYY-MM-DD');
            }

            $(this).val(date);
            $( "#target" ).submit();
        });
    }

    var isValidDate = function(value, format) {
        format = format || false;
        // lets parse the date to the best of our knowledge
        if (format) {
            value = parseDate(value);
        }

        var timestamp = Date.parse(value);

        return isNaN(timestamp) == false;
    }

    var parseDate = function(value) {
        var m = value.match(/^(\d{1,2})(\/|-)?(\d{1,2})(\/|-)?(\d{4})$/);
        if (m)
            value = m[5] + '-' + ("00" + m[3]).slice(-2) + '-' + ("00" + m[1]).slice(-2);

        return value;
    }

    bindDatePicker();
});


//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
// Sets the min-height of #page-wrapper to window size
/*
$(function() {
    $(window).bind("load resize", function() {
        topOffset = 50;
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    var url = window.location;
    var element = $('ul.nav a').filter(function() {
        return this.href == url || url.href.indexOf(this.href) == 0;
    }).addClass('active').parent().parent().addClass('in').parent();
    if (element.is('li')) {
        element.addClass('active');
    }
});

*/

// OffCanvas [START]
// Toggle on button click
$('.off-canvas-toggle').on('click', function(event) {
    event.preventDefault();
    $('body').toggleClass('off-canvas-active');
});

// Close on mouseup and touchend
$(document).on('mouseup touchend', function(event) {
    var offCanvas = $('.off-canvas')
    if (!offCanvas.is(event.target) && offCanvas.has(event.target).length === 0) {
        $('body').removeClass('off-canvas-active')
    }
});

// OffCanvas [ENDE]