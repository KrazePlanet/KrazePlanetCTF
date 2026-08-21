let emailValid = document.getElementById("email-valid");
let email = document.getElementById("email");
let emailFlag=true;
const validateEmail=()=> {          
    mailFormat=/^[A-Za-z\._\-0-9]*[@][A-Za-z]*[\.][a-z]{2,4}$/;
    if (email.value.match(mailFormat)) {
        emailFlag=true;
        emailValid.innerText = "";
        return true;
     
    } else {
        emailFlag=false;
        emailValid.innerText="Please enter a valid email";
        return false;
       
    }

}

let passValid = document.getElementById("pass-valid");
let pass = document.getElementById("pass");
let passFlag=true;
const validatePassword=()=>{
    mailFormat= /^[\S]{8,15}$/;
    if (pass.value.match(mailFormat)) {
        passFlag=true;
        passValid.innerText = "";
        return true;
    } else {
        passFlag=false;
        passValid.innerText="password must be 8 to 15 characters";
       return false;
    }

}
let submitBtn= document.getElementById("submit");     
setInterval(()=>{
    if (emailFlag & passFlag) {
        submitBtn.disabled=false;
    }else{
        validateEmail();
        submitBtn.disabled=true;
    }
},1000)