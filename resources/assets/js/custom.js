/** CUSTOM JS CODE HERE: */
$(document).ready(function(){
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

	/** Ajax handling for registering herbaria */
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
	/** Ajax handling for registering taxons */
        $("#checkapis").click(function(e) {
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
                        data: {'name': $('input[name="name"]').val()},
                        success: function (data) {
                                $( "#spinner" ).hide();
                                if ("error" in data) {
                                        $( "#ajax-error" ).collapse("show");
                                        $( "#ajax-error" ).text(data.error);
                                } else {
                                        if ($.isEmptyObject(data.bag)) {
                                                // ONLY removes the error div if request is success and no messages
                                                $( "#ajax-error" ).collapse("hide");
                                        } else {
                                                $( "#ajax-error" ).collapse("show");
                                                $( "#ajax-error" ).empty();
                                                var newul = $(document.createElement( "ul" ));
                                                $.each( data.bag, function( key, val ) {
                                                        var newli = $(document.createElement( "li" ));
                                                        newul.append( newli );
                                                        newli.text(val);
                                                });
                                                $( "#ajax-error" ).append(newul);
                                        }
                                        $("#level").val(data.apidata[0]);
                                        $("#author").val(data.apidata[1]);
                                        if (data.apidata[2]) {
                                                $("#valid").prop('checked', true);
                                        } else {
                                                $("#valid").prop('checked', false);
                                        }
                                        $("#bibreference").val(data.apidata[3]);
                                        if (data.apidata[4]) {
                                            $("#parent_id").val(data.apidata[4][0]);
                                            $("#parent_autocomplete").val(data.apidata[4][1]);
                                        } else {
                                            $("#parent_id").val("");
                                            $("#parent_autocomplete").val("");
                                        }
                                        if (data.apidata[5]) {
                                            $("#senior_id").val(data.apidata[5][0]);
                                            $("#senior_autocomplete").val(data.apidata[5][1]);
                                        } else {
                                            $("#senior_id").val("");
                                            $("#senior_autocomplete").val("");
                                        }
                                        $("#mobotkey").val(data.apidata[6]);
                                        $("#ipnikey").val(data.apidata[7]);
                                        $("#mycobankkey").val(data.apidata[8]);
                                }
                        },
                        error: function(e){ 
                                $( "#spinner" ).hide();
                                $( "#ajax-error" ).collapse("show");
                                $( "#ajax-error" ).text('Error sending AJAX request');
                        }
                })
        });

	/** For Location create and edit pages. The available fields change with changes on adm_level.
	 * The "vel" parameter determines the velocity in which the animation is made. **/
	function setLocationFields(vel) {
		var adm = $('#adm_level option:selected').val();
		if ("undefined" === typeof adm) {
			return; // nothing to do here...
		}
		switch (adm) {
			case "999": // point
				$("#super-geometry").hide(vel);
				$("#super-points").show(vel);
				$("#super-x").hide(vel);
				break;
			case "100": // plot
				$("#super-geometry").hide(vel);
				$("#super-points").show(vel);
				$("#super-x").show(vel);
				break;
			default: // other
				$("#super-geometry").show(vel);
				$("#super-points").hide(vel);
				$("#super-x").hide(vel);
		}
	}
	$("#adm_level").change(function() { setLocationFields(400); });
    // trigger this on page load
	setLocationFields(0);

    // For setting the visibility of fields in the voucher create/edit screens. 
    function setVoucherFields(vel) {
        var selector = $("#parent_type");
        switch (selector.val()) {
            case 'App\\Plant':
                $("#location_group").hide(vel);
                $("#project_group").hide(vel);
                $("#identification_group").hide(vel);
                $("#plant_group").show(vel);
                break;
            case 'App\\Location':
                $("#location_group").show(vel);
                $("#project_group").show(vel);
                $("#identification_group").show(vel);
                $("#plant_group").hide(vel);
                break;
        }
    }
    $("#parent_type").change(function() { setVoucherFields(400); });
    // trigger this on page load
    setVoucherFields(0);
});
