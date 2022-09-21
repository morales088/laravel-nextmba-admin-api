<!DOCTYPE>  
<html> 
<head>
      <title>Test</title>
</head> 
<body>  
    <h1>test</h1>

    <form id="login">
    <input name="email" value="" class="form-control" placeholder="Email" type="email">
    <input name="password" value="" class="form-control" placeholder="password" type="password">
    <button type="submit" value="Submit">Submit</button>

    </form>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <script type="text/javascript">

            $('#login').on('submit',function(){
                var inputz = $(this).serializeArray();
                var ajaxData = new FormData();

                $.each(inputz,function(key,input){
                    ajaxData.append(input.name,input.value);
                });
                
                $.ajax({
                    url: "http://nextuniversity.com/api/user/login",
                    type: "POST",
                    dataType: "json",
                    contentType: false,
                    processData: false,
                    cache: false,
                    data: ajaxData,
                    success:function(data){
                        console.log(data);
                    },error:function(e){
                        console.log(e);
                    }
                });

            return false;
            });
    </script>
</body>
</html> ​