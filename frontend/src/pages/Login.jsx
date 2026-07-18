import { useState } from "react";
import api from "../api/axios";


function Login(){

    const [email,setEmail] = useState("");
    const [password,setPassword] = useState("");


    const handleLogin = async (e)=>{

        e.preventDefault();


        try{

            const response = await api.post("/login",{

                email,
                password

            });


            // Get token and user data
            const { token, user } = response.data;


            // Save JWT token
            localStorage.setItem("token", token);


            // Save user information
            localStorage.setItem(
                "user",
                JSON.stringify(user)
            );


            console.log("Login successful:", user);


            // Redirect based on role
            if(user.role === "Admin"){

                window.location.href = "/admin";

            }
            else if(user.role === "Manager"){

                window.location.href = "/manager";

            }
            else if(user.role === "IT Support"){

                window.location.href = "/support";

            }
            else if(user.role === "Employee"){

                window.location.href = "/employee";

            }


        }
        catch(error){

            console.log(
                error.response?.data || "Login failed"
            );

        }

    };


    return (

        <div>

            <h1>HelpDeskPro Login</h1>


            <form onSubmit={handleLogin}>


                <input
                type="email"
                placeholder="Email"
                value={email}
                onChange={(e)=>setEmail(e.target.value)}
                />


                <input
                type="password"
                placeholder="Password"
                value={password}
                onChange={(e)=>setPassword(e.target.value)}
                />


                <button type="submit">
                    Login
                </button>


            </form>


        </div>

    );

}


export default Login;