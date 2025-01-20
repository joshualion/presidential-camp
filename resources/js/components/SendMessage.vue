<template>
  <div class="row">
    <div class="col-md-3 myUser card">
      <div class="card-header">
      <strong class="card-title">Chat History</strong>
          
      </div>
        <ul class="user ">
           
        <li> 
          <a href="">
            <img src="/no_image.jpg"
              alt="UserImage"
              class="userImg"
            />
            <span class="username text-center"> users</span>
          </a>
        </li>

      </ul>
    </div>

    <div class="col-md-9">
      <div class="card card-primary">
        <div class="active-contact card-header text-left myrow">
            <strong class="card-title"> Selected Users </strong> 
                   
        </div>
        <div class="card-body chat-msg">
          <ul class="chat">

           <li class="sender clearfix">
              <span class="chat-img left clearfix mx-2">
              <img src="/no_image.jpg"
                  class="userImg"
                  alt="userImg"
                />
              </span>
              <div class="chat-body2 clearfix">
                <div class="header clearfix">
                  <strong class="primary-font">Username</strong>
                  <small class="right text-muted">
                    11:30am 
                  </small>
                  <!-- //if send with product id  -->
                  <div class="text-center">
                      product name
                  <img src="/no_image.jpg"
                      alt="productImg"
                      width="60px;"
                    />
                  </div>
                </div>

                <p>Hi..</p>
              </div>
            </li>

        <!-- my part  -->
            <li class="buyer clearfix">
              <span class="chat-img right clearfix mx-2">
                <img src="/no_image.jpg"
                  class="userImg"
                  alt="userImg"
                />
                 </span>
              <div class="chat-body clearfix">
                <div class="header clearfix">
                  <small class="left text-muted"
                    >12:10pm</small>
                   <div class="text-center">
                      Product name
                   <img src="/no_image.jpg"
                      alt="prouductImage"
                      width="60px;"
                    />
                  </div>
                </div>
                <p>Hello...</p>
              </div>
            </li>
        
            <li class="sender clearfix">
              <span class="chat-img left clearfix mx-2"> </span>
            </li>

          </ul>
        </div>
        <div class="card-footer">
          <div class="input-group">
            <textarea
              id="btn-input"
              type="text"
              class="form-control input-sm styled-textarea"
              placeholder="Type your message here..."
            ></textarea>
            
            <span class="input-group-btn">
              <button class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  props: {
    darkThemeColor: {
      type: String,
      default: '#008751', // fallback in case it's not passed
    },
  },
};
</script>
