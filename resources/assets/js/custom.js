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
				$("#super-uc").show(vel);
				break;
			case "100": // plot
				$("#super-geometry").hide(vel);
				$("#super-points").show(vel);
				$("#super-x").show(vel);
				$("#super-uc").show(vel);
				break;
			default: // other
				$("#super-geometry").show(vel);
				$("#super-points").hide(vel);
				$("#super-x").hide(vel);
				$("#super-uc").hide(vel);
		}
	}
	$("#adm_level").change(function() { setLocationFields(400); });
    // trigger this on page load
	setLocationFields(0);

	/** Ajax handling for autodetecting location */
	$("#autodetect").click(function(e) {
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
            data: {
                'adm_level': $('#adm_level option:selected').val(),
                'geom': $('input[name="geom"]').val(),
                'lat1': $('input[name="lat1"]').val(),
                'lat2': $('input[name="lat2"]').val(),
                'lat3': $('input[name="lat3"]').val(),
                'latO': $('#latO checked').val(),
                'long1': $('input[name="long1"]').val(),
                'long2': $('input[name="long2"]').val(),
                'long3': $('input[name="long3"]').val(),
                'longO': $('#longO checked').val()
            },
			success: function (data) {
				$( "#spinner" ).hide();
				if ("error" in data) {
					$( "#ajax-error" ).collapse("show");
					$( "#ajax-error" ).text(data.error);
				} else {
					// ONLY removes the error if request is success
					$( "#ajax-error" ).collapse("hide");
					$("#parent_autocomplete").val(data.detectdata[0]);
					$("#parent_id").val(data.detectdata[1]);
					$("#uc_autocomplete").val(data.detectdata[2]);
					$("#uc_id").val(data.detectdata[3]);
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
