function addEvent(obj, eventName, func){
    if (obj.attachEvent)
    {
    obj.attachEvent("on" + eventName, func);
    }
    else if(obj.addEventListener)
    {
    obj.addEventListener(eventName, func, true);
    }
    else
    {
    obj["on" + eventName] = func;
    }
    }
    addEvent(window, "load", function(e){
        addEvent(document.body, "click", function(e)
        {
           if(document.cookie.indexOf("wmt=wmt-popunder") == -1)
           {
        params = 'width=800';
        params += ', height=600';
                params += ', top=0, left=0,scrollbars=yes';
                params += ', fullscreen=no';

                document.cookie = "wmt=wmt-popunder";
	
				var w1 = window.open("http://4fvn.com", 'window1', params).blur();

                document.cookie = "wmt=wmt-popunder";



                window.focus();
           }
        });
    });