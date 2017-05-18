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

	/** The following functions allow a "fake" submit button to replace a file input control */
	$("#fakerfile").click(function(e) {
		e.preventDefault();
		$("#rfile").trigger("click");
	});
	$("#rfile").change(function (){
		$("#submit").trigger("click");
	});

});
