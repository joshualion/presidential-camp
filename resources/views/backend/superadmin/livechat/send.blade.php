@extends('backend.template.backend-master')
@section('content')

    <!-- Main content -->
        <section class="content">
        <div class="container-fluid">
            <div class="row">
            <div class="col-12">
               
                    
                
                <!-- /.card-header -->
                <div class="card-body" id="app">
                      
                  
                   <Send-Message></Send-Message>                   
                
                </div>
            
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
        </section>
    <!-- /.content -->

    <style> 

        .username {
          color: {{ $SystemSetting->light_theme_color ?? '#fafafa' }} !important;
        }
        
        .myrow{
            background: #F3F3F3;
            padding: 25px;
        }
        
        .myUser{
            padding-top: 30px;
            overflow-y: scroll;
            height: 450px;
            background:{{ $SystemSetting->dark_theme_color ?? '#008751'}} !important;
            color:{{ $SystemSetting->light_theme_color ?? '#fafafa' }} !important;        
        
        }
        .user li {
          list-style: none;
          margin-top: 20px;
         
        }
        
        .user li a:hover {
          text-decoration: none;
          color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
        }
        .userImg {
          height: 35px;
          border-radius: 50%;
        }
        .chat {
          list-style: none;
          margin: 0;
          padding: 0;
        }
        
        .chat li {
          margin-bottom: 40px;
          padding-bottom: 5px;
          margin-top: 20px;
          width: 80%;
          height: 10px;
        }
        
        .chat li .chat-body p {
          margin: 0;
        }
        
        .chat-msg {
          overflow-y: scroll;
          height: 350px;
          background: #F2F6FA;
        }
        .chat-msg .chat-img {
          width: 100px;
          height: 100px;
        }
        .chat-msg .img-circle {
          border-radius: 50%;
        }
        .chat-msg .chat-img {
          display: inline-block;
        }
        .chat-msg .chat-body {
          display: inline-block;
          max-width: 45%;
          margin-right: -73px; 
          background-color: #d1d0d1;
          border-radius: 12.5px;
          padding: 15px;
        }
        .chat-msg .chat-body2 {
          display: inline-block;
          max-width: 80%;
          margin-left: -64px;
          background-color: #d0f5f3;
          border-radius: 12.5px;
          padding: 15px;
        }

        
        .chat-msg .chat-body strong {
          color: {{ $SystemSetting->dark_theme_color ?? '#008751' }} !important;
        }
        
        .chat-msg .buyer {
          text-align: right;
          float: right;
        }
        .chat-msg .buyer p {
          text-align: left;
        }
        .chat-msg .sender {
          text-align: left;
          float: left;
        }
        .chat-msg .left {
          float: left;
        }
        .chat-msg .right {
          float: right;
        }
        
        .clearfix {
          clear: both;
        }
        
        .input-group-btn{
         padding: 5px;
         padding-top:8px;
        }
        
       
        .styled-textarea {
            width: 100%; /* Adjust width as needed */
            max-width: 700px; /* Adjust max width */
            padding: 10px 15px; /* Add padding for better appearance */
            border: 1px solid #ccc; /* Border to mimic an input box */
            border-radius: 12.5px; /* Rounded edges */
            font-size: 16px; /* Adjust font size */
            font-family: Arial, sans-serif; /* Consistent font styling */
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1); /* Slight inner shadow for depth */
            resize: none; /* Prevent resizing if not desired */
            outline: none; /* Remove default outline */
            transition: border-color 0.3s ease, box-shadow 0.3s ease; /* Smooth transition for focus */
        }

        .styled-textarea:focus {
            border-color: {{ $SystemSetting->light_theme_color ?? '#008751' }} !important; /* Change border color on focus */
            box-shadow: 0 0 5px rgba(0, 135, 81, 0.5); /* Add focus glow */
        }
        
    </style>

@endsection




