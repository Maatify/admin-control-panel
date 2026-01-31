/** @format */
/**
 * Created by Maatify.dev
 * User: Maatify.dev
 * Date: 2025-11-04
 * Time: 10:04 AM
 * https://www.Maatify.dev
 */

const RES_MSG = {
    successMsgEdit: "Data edited successfully",
    successMsg: "Data saved successfully",
    successSms: "Message sent successfully",
    wrongCap: "Please use 'I am not a robot'",
    successSubMsg: "Subscription successful",
    successImgMsg: "File uploaded successfully",
    suspendedAccount: "Account suspended",
    errorMsg: "There is an error. Please try again later or contact technical support",
    noDataUpdated: "No data to update",
    wrongPage: "Wrong page",
    makeSure1: "Make sure to write",
    makeSure2: "correctly",
    makeSure3: "Make sure to select",
    exist: "Already exists",
    notExist: "Does not exist",
    notAllowed: "Not allowed to use",
    notVerified: "Not Verified",
    inToUse: "In To Use",
    unexpected: "Unexpected to send",
    emailVerified: "Email already verified",
    selectImg: "Make sure to select an image for upload",
    imgSize: "File size should not exceed 10 megabytes",
    imgType: "File format must be (jpeg, png, gif, jpg, or pdf)",
    TooMany: "Too many pending OTP requests for this recipient",
    TooManyDevice: "Too many pending OTP requests for this device",
    waitTimeLeft: "Please wait $timeLeft seconds before retrying",
    expiredOTP: "Expired OTP code",
    invalidOTP: "Invalid/Incorrect OTP code",
    OTPNotFound: "Not Found OTP Code",
    codeExpired: "Code expired",
    wrongCredentials: "Incorrect Credentials",
    PendingAccount: "Approval Pending Account",
    phoneVerified: "Phone is Already Verified",
    insufficientBalance: "Insufficient Balance",
    captchaError: "Error In Captcha",
};

/**
 * Factory function version (no class)
 */

// ====== Alerts ======
function showAlert(type, message, duration) {
    // Overload: if only 1 arg, treat as message (success)
    if (arguments.length === 1) {
        message = type;
        type = 's';
    }
    
    // Overload: if 2 args (type, message) or (message, duration) -> ambiguous if type is not s/w/d
    // Assume if type is not s/w/d, it is a message and defaults to s? 
    // But let's stick to safe assumption: 
    if (!['s', 'w', 'd'].includes(type) && type !== undefined) {
        // If type is not a valid code, treat it as message, default to success
            // If second arg exists, it might be duration? ignoring for now to keep simple
        message = type;
        type = 's';
    }

    const container = document.getElementById('alert-container');
    if (container) {
        const styles = {
            s: "bg-green-100 border-green-400 text-green-700",
            w: "bg-yellow-100 border-yellow-400 text-yellow-700",
            d: "bg-red-100 border-red-400 text-red-700"
        };
        const titles = {
            s: "Success",
            w: "Warning",
            d: "Error"
        };

        const html = `
            <div class="border-l-4 p-4 ${styles[type]} rounded shadow-md relative absolute top-10 left-10  z-9999999 w-150" role="alert">
                <strong class="font-bold">${titles[type]}:</strong>
                <span class="block sm:inline">${message}</span>
            </div>`;
        
        container.innerHTML = html;
        container.style.display = 'block';

        setTimeout(() => {
            container.innerHTML = '';
            container.style.display = 'none';
        }, duration || 5000);
        return;
    }

    // Fallback to legacy selectors if #alert-container not found
    const alertTypes = {
        s: ".alert-success",
        w: ".alert-warning",
        d: ".alert-error",
    };

    Object.values(alertTypes).forEach((selector) => {
        const el = document.querySelector(selector);
        if (el) el.style.display = "none";
    });

    const alertElement = document.querySelector(alertTypes[type]);
    if (alertElement) {
        alertElement.style.display = "flex";
        const p = alertElement.querySelector("p");
        if(p) p.innerHTML = message;
        else alertElement.textContent = message;

        setTimeout(() => {
            alertElement.style.display = "none";
        }, duration || (type === "d" ? 15000 : 5000));
    } else {
            // Final fallback
            alert(message);
    }
}

function createCallbackHandler(baseURL = "/api") {
    let isLoading = false;

    // ====== Progress Bar ======
    function updateProgressBar(width) {
        const bar = document.getElementById("myBar");
        const progressContainer = document.getElementById("myProgress");

        if (!bar || !progressContainer) return;

        if (width === 0) {
            progressContainer.style.display = "none";
            return;
        }

        progressContainer.style.display = "flex";
        bar.style.width = `${width}%`;
        progressContainer.querySelector('img').style.width = `${width / 3}%`;
    }

    function startProgress() {
        if (isLoading) return;
        isLoading = true;

        let width = 1;
        updateProgressBar(width);

        const id = setInterval(() => {
            width++;
            if (width >= 100) {
                clearInterval(id);
                isLoading = false;
                updateProgressBar(0);
            } else {
                updateProgressBar(width);
            }
        }, 10);
    }

    // ====== UI toggle ======
    function toggleElements(disabled) {
        document.querySelectorAll("a, button").forEach((item) => {
            if (disabled) {
                item.setAttribute("disabled", "disabled");
                item.style.pointerEvents = "none";
            } else {
                item.removeAttribute("disabled");
                item.style.pointerEvents = "auto";
            }
        });
    }

    // ====== Redirects ======
    function handleAuthRedirect(action, data) {
        switch (action) {
            case "Auth":
                window.location.href = "/index.php?page=auth";
                break;
            case "Login":
                window.location.href = "/project/index.php?page=login";
                break;
            case "AuthRegister":
                localStorage.setItem("code", data?.g_auth_code);
                localStorage.setItem("codeBase64", data?.g_auth_base64);
                window.location.href = "/index.php?page=auth-register";
                break;
            case "EmailConfirm":
                window.location.href = "/index.php?page=confirm-email";
                break;
            case "ChangePassword":
                window.location.href = "/index.php?page=change-password";
                break;
            default:
                window.location.href = "/project/index.php?page=login";
        }
    }

    // ====== Error handlers ======
    function showWrongError(description, info) {
        const message = RES_MSG[description]
            ? `${RES_MSG.makeSure1} ${RES_MSG[description]} ${RES_MSG.makeSure2}`
            : RES_MSG.errorMsg;
        showAlert("d", `${message} (${description})-(${info})`);
    }

    function showExistError(description, info) {
        showAlert("d", `${RES_MSG[description] || RES_MSG.exist} (${description})-(${info})`);
    }

    function showNotExistError(description, info) {
        showAlert("d", `${RES_MSG[description] || RES_MSG.errorMsg} ${RES_MSG.notExist} (${description})-(${info})`);
    }

    function showNotAllowedError(description, info) {
        showAlert("d", `${RES_MSG.notAllowed} ${RES_MSG[description] || ""} (${description})-(${info})`);
    }

    function showNotVerifiedError(description, info) {
        showAlert("d", `${RES_MSG[description] || ""} ${RES_MSG.notVerified} (${description})-(${info})`);
    }

    function showInToUseError(description, info) {
        showAlert("d", `${RES_MSG[description] || ""} ${RES_MSG.inToUse} (${description})-(${info})`);
    }

    function showUnexpectedError(description, info) {
        showAlert("d", `${RES_MSG.unexpected} ${RES_MSG[description] || ""} (${description})-(${info})`);
    }

    function handleErrorResponse(res, description, info) {
        const handlers = {
            1000: showWrongError,
            2000: showWrongError,
            3000: showExistError,
            4000: showWrongError,
            5000: showNotVerifiedError,
            6000: showNotExistError,
            7000: showNotAllowedError,
            8000: showInToUseError,
            9000: showUnexpectedError,
            40001: () => showAlert("w", `${RES_MSG.noDataUpdated} (${res}-${description})`),
            2025: () => showAlert("d", `${RES_MSG.wrongCredentials} (${res}-${description})`),
            405000: () => handleAuthRedirect(description, info),
        };

        if (handlers[res]) handlers[res](description, info);
        else showAlert("d", `${RES_MSG.errorMsg} (${res}-${description})`);
    }

    function handleApiError(error) {
        const status = error?.status || error?.response?.status;
        if (!status) {
            showAlert("d", `${RES_MSG.errorMsg} (Unknown)`);
            return;
        }

        switch (status) {
            case 400:
                handleErrorResponse(
                    error.response?.data?.response,
                    error.response?.data?.var,
                    error.response?.data?.more_info
                );
                break;
            case 403:
                window.location.href = "/home";
                break;
            case 419:
            case 498:
                window.location.href = "/index.php?page=login";
                break;
            default:
                showAlert("d", `${RES_MSG.errorMsg} (${status})`);
        }
    }

    // ====== Main API Call ======
    async function callback(callBackMethod = "", params = {}) {
        startProgress();
        toggleElements(true);
function getAuthToken() {
        return '';
    }
        try {
            console.log(baseURL+"/"+callBackMethod)
            // const res = await axios.post(`${baseURL}/${callBackMethod}`, params, { withCredentials: true })
            const res = await fetch(`${baseURL}/${callBackMethod}`, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': getAuthToken()
                },
               
                credentials: "include",
                body: JSON.stringify(params),
            });

            const data = await res.json();
            console.log("API Response:", data);

            const { response: value, action, more_info } = data.data;

            if (value === 200) {
                return data;
            } else if (value === 405000) {
                handleAuthRedirect(action, more_info);
                return data;
            } else {
                handleErrorResponse(value, more_info, value);
                return data;
            }
        } catch (err) {
            handleApiError(err);
        } finally {
            toggleElements(false);
        }
    }

    return {
        callback,
        showAlert,
        handleAuthRedirect,
    };
}

// ====== Example usage ======
/*
const handler = createCallbackHandler("https://api.example.com");

document.getElementById("sendBtn").addEventListener("click", () => {
    handler.callback("login", { username: "user", password: "pass" });
});
*/
