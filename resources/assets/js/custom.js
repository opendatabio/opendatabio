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
                                        }
                                        if (data.apidata[5]) {
                                            $("#senior_id").val(data.apidata[5][0]);
                                            $("#senior_autocomplete").val(data.apidata[5][1]);
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
	$("#adm_level").change(function() {
		setLocationFields(400);
	});
    // trigger this on page load
	setLocationFields(0);

    /** For use in multiple selectors. Right now used in Edit Person for specialists */
    // Original: http://odyniec.net/articles/multiple-select-fields/
    // The elements are tied by the NAME, CLASS AND ID attributes, use them as
    // ul: ID specialist-ul 
    // inputs: NAME specialist[] CLASS .multipleSelector
    // select: NAME specialist-ms CLASS .multi-select
        $(".multi-select").change(function()
                {
                    var $name = $(this).attr('name');
                    $name = $name.substring(0, $name.length-3);
                    var $ul = $("#" + $name + "-ul");
                        if ( $(this).val() === "") return;
                        if ($ul.find('input[value=' + $(this).val() + ']').length == 0)
                                $ul.append('<span class="multipleSelector" onclick="$(this).remove();">' +
                                        '<input type="hidden" name="' + $name + '[]" value="' +
                                        $(this).val() + '" /> ' +
                                        $(this).find('option:selected').text() + '</span>');
                });
        $(".multipleSelector").click(function() {
                $(this).remove();
        });

});
