@extends('app')

@section('content')
        
        <?php echo "
        
        
            <p id = 'edit'>
                edit profile
            </p>
            
            <form name ='form1'
                  id = 'form1'
                  method = 'post'
                  action = '/user/edit'>
            
                姓名:<input type = 'text' name = 'username' value ='" . $username . "'>
                <br>
                性別:<input type = 'text' name = 'sex' value ='" . $sex . "'>
                <br>
                生日:<input type = 'text' name = 'birthdate' value ='" . $birthdate . "'>
                <br>
                電子郵件:<input type = 'text' name = 'email' value ='" . $email . "'>
                <br>
                手機號碼:<input type = 'text' name = 'cellphone' value ='" . $cellphone . "'>
                <br>
                
                <input type = 'Submit' class = 'button' name = 'submit1' value ='save'>
                <input type = 'button' class = 'button' value ='reset' onclick=\"location.href=''\">
                <input type = 'button' class = 'button' value ='menu' onclick=\"location.href='" . url('/home') . "'\">
                <input type = 'button' class = 'button' value ='next' onclick=\"location.href='" . url('/home') . "'\">
            
            ";
            
        ?>
        
            <!!laravel will check token if we have post some message to other pages.>
            <?php echo csrf_field(); ?>
        </form>

@endsection
