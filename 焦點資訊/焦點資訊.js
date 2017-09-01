$("#posts div").hide();
$("a[rel='category-1']").click(function() {
	$("#posts div").hide(); // gather all the div tags under the element with the id pages and hide them.
	$("#div1").show(); // Show the div with the class of .divX where X is the number stored in the data-id of the object that was clicked.
   });
$("button").click(function() {
	$("#posts div").hide(); // gather all the div tags under the element with the id pages and hide them.
	$("#div1").show(); // Show the div with the class of .divX where X is the number stored in the data-id of the object that was clicked.
  });