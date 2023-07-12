$(function() {
    $("#status").change(function(){
        Swal.fire({
      title: 'Are you sure?',
      text: "You won't change the status!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Change!'
    }).then((result) => {
    if (result.value) {
        var th = $(this);
        let url = 'user-status/'+th.attr('data-id')+'/'+th.val()
                
        Utils.get(url, function(resp){ 
          if(resp.success){
            Swal.fire(
                'Status!',
                'Your status has been changed.',
                'success'
            )
          }else{
             Swal.fire(
                'Error!',
                'Your status has not been changed.',
                'some error'
            )
        }
        });
        
      }
    })
        
    });
});
    