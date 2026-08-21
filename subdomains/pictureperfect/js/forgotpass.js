let passwordValid = document.getElementById("pass-validation");
let password = document.getElementById("pass");
let passwordFlag=true;
const validatingPassword=()=>{
    mailFormat= /^[\S]{8,15}$/;
    if (password.value.match(mailFormat)) {
        passwordFlag=true;
        passwordValid.innerText = "";
        return true;
    } else {
        passwordFlag=false;
        passwordValid.innerText="password must be 8 to 15 characters";
       return false;
    }

}
let submitButn= document.getElementById("submit");     
setInterval(()=>{
    if (passwordFlag) {
        submitButn.disabled=false;
    }else{
        submitButn.disabled=true;
    }
},1000)