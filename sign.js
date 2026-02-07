let isLoggedIn = true;
let isAdmin = false;

const profileImg = "user-icon.png";

function updateNavbar() {
  const authArea = document.getElementById("authArea");

  if (isLoggedIn) {
    if (isAdmin) {
      authArea.innerHTML = `
       <a href="admin.html" style="color: white;text-decoration: none;font-size: 30px;margin-right: 25px;display: inline-block;transform: translateY(-5px);">Admin Page</a>
                <a href="account.html"><img src="${profileImg}" style="width:55px;height:55px;border-radius:50%;cursor:pointer;"></a>
      `;
    } 
    else {
      authArea.innerHTML = `<a href="account.html"><img src="${profileImg}" style="width:55px;height:55px;border-radius:50%;cursor:pointer;"></a>
      `;
    }

  } else {
    
    authArea.innerHTML = `
      <a href="signin.html" style="background:white;padding:12px 22px;border-radius:20px;color:black;">Sign In</a>
      <a href="signup.html" style="background:white;padding:12px 22px;border-radius:20px;color:black;margin-left:10px;">Sign Up</a>
    `;
  }
}

updateNavbar();
