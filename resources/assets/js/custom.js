/** CUSTOM JS CODE HERE: */
$(document).ready(function(){
    // chainable custom autocomplete with some global defaults
    // use invalidateCallback for cleanups
    // If this is edited, NOTICE that measurements/create use a pure devbridge version and
    // must be updated as well!
    $.fn.odbAutocomplete = function(url, id_element, noResult, invalidateCallback, params, selectCallback) {
        return this.devbridgeAutocomplete({
            serviceUrl: url,
            onSelect: function (suggestion) {
                $(id_element).val(suggestion.data);
                if (typeof selectCallback === "function")
                    selectCallback(suggestion);
            },
            onInvalidateSelection: function() {
                $(id_element).val(null);
                if (typeof invalidateCallback === "function")
                    invalidateCallback();
            },
            minChars: 1,
            onSearchStart: function() {
                $(".minispinner").remove();
                $(this).after("<div class='spinner minispinner'></div>");
            },
            onSearchComplete: function() {
                $(".minispinner").remove();
            },
            showNoSuggestionNotice: true,
            noSuggestionNotice: noResult,
            params: params
        });
    };
	/** Abbreviation helper for the Person entity */
	$("#full_name").blur(function () {
		/* Only changes value if the field is empty */
		var abb = $("#abbreviation");
		if (abb.val() === "") {
			var txt = $(this).val().toUpperCase().trim().split(" ");
			/* Stores the person last name, which will not be abbreviated */
			var lname = txt.pop();
			for (i = 0; i < txt.length; i++) {
				txt[i] = txt[i].substring(0, 1) + ".";
			}
			if (txt.length > 0) {
				abb.val(lname + ", " + txt.join(" "));
			} else {
				abb.val(lname);
			}
		}
	});

	/** The following functions allow a "fake" submit button to replace a file input control.
	 *  Used on every view that accepts a file input */
	$("#fakerfile").click(function(e) {
		e.preventDefault();
		$("#rfile").trigger("click");
	});
	$("#rfile").change(function (){
		$("#submit").trigger("click");
	});

	/** Ajax handling for registering biocollections */
	$("#checkih").click(function(e) {
		$( "#spinner" ).css('display', 'inline-block');
		$.ajaxSetup({ // sends the cross-forgery token!
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		})
		e.preventDefault(); // does not allow the form to submit
		$.ajax({
			type: "POST",
			url: $('input[name="route-url"]').val(),
			dataType: 'json',
			data: {'acronym': $('input[name="acronym"]').val()},
			success: function (data) {
				$( "#spinner" ).hide();
				if ("error" in data) {
					$( "#ajax-error" ).collapse("show");
					$( "#ajax-error" ).text(data.error);
				} else {
					// ONLY removes the error if request is success
					$( "#ajax-error" ).collapse("hide");
					$("#irn").val(data.ihdata[0]);
					$("#name").val(data.ihdata[1]);
				}
			},
			error: function(e){
				$( "#spinner" ).hide();
				$( "#ajax-error" ).collapse("show");
				$( "#ajax-error" ).text('Error sending AJAX request');
			}
		})
	});
});
