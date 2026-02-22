$(function() {
	
// ---------------------------
// DELETE
// ---------------------------

  $('form button').click(function() {
     // console.log('Item deleted!');
     
     

         $.ajax({
                    url: 'admin/crm/ajax',
                    type: 'POST',
                    data: "object_type=mail&"+($('form').serialize()),
                    success: function(result) {
                      $('#MediaclassCRMAjaxResponse').hide().html('<div class="alert alert-success" style="padding: 10px 15px 6px 15px;">'+result+'</div>').fadeIn();
                      $('#mailForm').animate({ height: 0, opacity: 0 }, 'slow');
                      
                    },
               error: function (xhr, ajaxOptions, thrownError) {
            var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
            $('#MediaclassCRMAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result
              +'</div>').delay(2222000).fadeOut(function() {
                        $('#MediaclassCRMAjaxResponse').html('');
                     });
            //alert(' Error status : '+ xhr.status+ "\n Thrown Error : "+ thrownError +"\n Error : "+ xhr.responseText);
            }
        });

  });
})