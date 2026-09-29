<x-welcome-layout>

    <div class="landing-page">

        <div class="circle circle-1"></div>
        <div class="circle circle-2"></div>

        <section class="content">

            <div class="logo">
                <img src="{{ url('frontend/img/logo.png') }}" alt="DinKin Logo" width="85" height="85">
            </div>

            <div class="system-label">
                DinKin
            </div>

            <h1>
                <span>Tournament System</span>
            </h1>

            <a href="{{ route('public.tournaments') }}" class="get-started">
                Get Started
                <i class="fa-solid fa-arrow-right"></i>
            </a>

            <div class="text-center">
                <button id="installBtn" class="get-started" style="display: none;">
                    Install Now
                </button>
            </div>

            <div class="footer-text">
                Empowering Tournaments Through Digital Innovation
                <br>

                &copy; {{ date('Y') }} DinKin. All rights reserved.
                <br><br>

                Developed by
                <a href="https://www.facebook.com/klarkhowellucion111821" target="_blank" rel="noopener noreferrer">
                    KHDLucion
                </a>
            </div>

        </section>

    </div>


    <style>
        /* =====================================================
           FULL PAGE
        ===================================================== */

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
            overflow-x: hidden;
        }

        body {
            background: #0b1f17;
        }

        .landing-page {
            position: relative;

            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            box-sizing: border-box;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            position: relative;
            z-index: 5;

            width: 100%;
            max-width: 650px;

            padding: 40px 25px;

            box-sizing: border-box;

            text-align: center;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .logo {
            margin-bottom: 12px;
        }

        .logo img {
            display: block;

            width: 85px;
            height: 85px;

            object-fit: contain;
        }


        /* =====================================================
           SYSTEM LABEL
        ===================================================== */

        .system-label {
            font-size: 14px;
            font-weight: 700;

            letter-spacing: 4px;
            text-transform: uppercase;

            margin-bottom: 10px;
        }


        /* =====================================================
           TITLE
        ===================================================== */

        .content h1 {
            margin: 0 0 30px;

            font-size: clamp(30px, 6vw, 52px);
            line-height: 1.15;

            font-weight: 700;
        }

        .content h1 span {
            display: inline-block;
        }


        /* =====================================================
           BUTTON
        ===================================================== */

        .get-started {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 10px;

            min-width: 190px;

            padding: 13px 24px;

            border: none;
            border-radius: 50px;

            text-decoration: none;

            cursor: pointer;

            box-sizing: border-box;
        }

        .get-started i {
            font-size: 14px;
        }

        #installBtn {
            margin-top: 15px;
        }


        /* =====================================================
           FOOTER
        ===================================================== */

        .footer-text {
            margin-top: 45px;

            font-size: 12px;
            line-height: 1.6;

            opacity: 0.8;
        }

        .footer-text a {
            color: aqua;
            text-decoration: none;
        }


        /* =====================================================
           DECORATIVE CIRCLES
        ===================================================== */

        .circle {
            position: absolute;

            border-radius: 50%;

            pointer-events: none;
        }

        .circle-1 {
            width: 350px;
            height: 350px;

            top: -180px;
            right: -120px;
        }

        .circle-2 {
            width: 300px;
            height: 300px;

            bottom: -160px;
            left: -130px;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 576px) {

            .landing-page {
                min-height: 100dvh;

                padding: 0;
            }

            .content {
                min-height: 100dvh;

                padding: 30px 20px;

                justify-content: center;
            }

            .logo img {
                width: 70px;
                height: 70px;
            }

            .system-label {
                font-size: 12px;
                letter-spacing: 3px;

                margin-top: 5px;
            }

            .content h1 {
                font-size: 30px;

                margin-bottom: 25px;
            }

            .get-started {
                width: 100%;
                max-width: 260px;

                padding: 13px 20px;
            }

            .footer-text {
                margin-top: 30px;

                font-size: 11px;

                max-width: 300px;
            }

            .circle-1 {
                width: 230px;
                height: 230px;

                top: -130px;
                right: -100px;
            }

            .circle-2 {
                width: 200px;
                height: 200px;

                bottom: -120px;
                left: -100px;
            }
        }


        /* =====================================================
           VERY SMALL PHONES
        ===================================================== */

        @media (max-height: 650px) and (max-width: 576px) {

            .content {
                padding-top: 20px;
                padding-bottom: 20px;
            }

            .logo img {
                width: 60px;
                height: 60px;
            }

            .system-label {
                margin-bottom: 5px;
            }

            .content h1 {
                font-size: 26px;
                margin-bottom: 18px;
            }

            .footer-text {
                margin-top: 20px;
            }

        }
    </style>


    <script>
        let deferredPrompt;

        const installBtn = document.getElementById("installBtn");

        window.addEventListener("beforeinstallprompt", (e) => {

            e.preventDefault();

            deferredPrompt = e;

            installBtn.style.display = "inline-flex";

        });


        installBtn.addEventListener("click", async () => {

            if (!deferredPrompt) {
                return;
            }

            installBtn.style.display = "none";

            deferredPrompt.prompt();

            const choiceResult = await deferredPrompt.userChoice;

            if (choiceResult.outcome === "accepted") {
                console.log("User accepted the A2HS prompt");
            } else {
                console.log("User dismissed the A2HS prompt");
            }

            deferredPrompt = null;

        });


        window.addEventListener("appinstalled", () => {

            installBtn.style.display = "none";

            deferredPrompt = null;

        });
    </script>

</x-welcome-layout>
