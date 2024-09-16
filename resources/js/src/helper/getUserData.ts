import { LoginResponseType } from "../pages/auth/action/login";

export function getUserData(): LoginResponseType | undefined {
    try {

        const userData = localStorage.getItem("userData");

        if (typeof userData !== 'object') {
            const connectedUser: LoginResponseType = JSON.parse(userData);

            return connectedUser
        }
    } catch (err) {
        console.log((err as Error).message);
    }
}