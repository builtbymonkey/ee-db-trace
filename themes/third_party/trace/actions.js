$(document).ready(function()
{
    //alert('sdfsdf');
  set_query_widths();
    
});

function set_query_widths()
{
    
      $('.trace-query').each(function()
      {
       $(this).css('width',$(this).parent('td').width());
      });

  
}

