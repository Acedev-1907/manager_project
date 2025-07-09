<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://kit.fontawesome.com/d615c16483.js" crossorigin="anonymous"></script>
    <title>App Task</title>
</head>
{{-- <script>
    const randomizeColour = () => {
        const colours = ["#22d3ee", "#a78bfa", "#f9a8d4"];
        const random = Math.floor(Math.random() * colours.length)
        return colours[random];
    }

    const randomizeAnimation = () => {
        const animations = ["fall", "fall-2", "fall-3"];
        const random = Math.floor(Math.random() * animations.length)
        return animations[random];
    }

    const handleMove = (e) => {

        if (window.innerWidth >= 1024) {
            const star = document.createElement("span");
            const glow = document.createElement("div");

            star.className = "star fa-solid fa-star";
            star.style.color = randomizeColour();
            const randomAnimation = randomizeAnimation();
            star.style.animation = `${randomAnimation} 5s forwards`;
            star.style.left = `${e.x}px`;
            star.style.top = `${e.y}px`;

            glow.className = "glow";
            glow.style.left = `${e.x}px`;
            glow.style.top = `${e.y}px`;

            document.body.appendChild(star);
            document.body.appendChild(glow);


            setTimeout(() => {
                document.body.removeChild(star)
            }, 1000)

            setTimeout(() => {
                document.body.removeChild(glow)
            }, 100)
        }
    }
    window.onmousemove = (e) => {
        handleMove(e);
    }
</script>
<style>
    .hovered {
        background-color: rgba(30, 233, 81, 0.317);
        border: 2px dashed #3cff00;
        transition: background-color 0.3s ease, border 0.3s ease;
    }

    .pending-column {
        transition: transform 0.3s ease;
    }

    .pending-column.hovered {
        transform: scale(1.05);
    }

    .card-direct {
        max-height: 82%;
        overflow-y: auto;
    }

    @media screen and (min-width: 1024px) {
        body {
            overflow: hidden;
        }

        .card.card-task {
            height: 420px;
        }

        .star {
            position: absolute;
            pointer-events: none;
            margin: 10px;
            z-index: -2;
            animation: fall 5s forwards;
        }

        .glow {
            box-shadow: 0px 0px 16px 7px #a78bfa;
            height: 1px;
            width: 1px;
            z-index: -1;
            position: absolute;
        }

        /* Animations */
        @keyframes fall {
            0% {
                transform: translate(0px, 0px) rotateX(45deg) rotateY(30deg) rotateZ(0deg) scale(0.25);
                opacity: 0;
            }

            5% {
                transform: translate(10px, -10px) rotateX(45deg) rotateY(30deg) rotateZ(0deg) scale(1);
                opacity: 1;
            }

            100% {
                transform: translate(25px, 200px) rotateX(180deg) rotateY(270deg) rotateZ(90deg) scale(1);
                opacity: 0;
            }
        }

        @keyframes fall-2 {
            0% {
                transform: translate(0px, 0px) rotateX(-20deg) rotateY(10deg) scale(0.25);
                opacity: 0;
            }

            10% {
                transform: translate(-10px, -5px) rotateX(-20deg) rotateY(10deg) scale(1);
                opacity: 1;
            }

            100% {
                transform: translate(-10px, 160px) rotateX(-90deg) rotateY(45deg) scale(0.25);
                opacity: 0;
            }
        }

        @keyframes fall-3 {
            0% {
                transform: translate(0px, 0px) rotateX(0deg) rotateY(45deg) scale(0.5);
                opacity: 0;
            }

            15% {
                transform: translate(7px, 5px) rotateX(0deg) rotateY(45deg) scale(1);
                opacity: 1;
            }

            100% {
                transform: translate(20px, 120px) rotateX(-180deg) rotateY(-90deg) scale(0.5);
                opacity: 0;
            }
        }
    }
</style> --}}

<body>
    <div id="app"></div>

    @vite(['resources/js/app.ts', 'resources/css/app.css'])
    <!-- loading script with vite blade directive -->
</body>


</html>
