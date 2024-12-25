
gsap.registerPlugin(MotionPathPlugin);

let pathLength, pathBoundingBox, startY, finishDistance;
let pathSpeed = 1; 
let arrowSpeed = 1; 
const pathSegments = [
    { start: 0, end: 1, pathMultiplier: 1, arrowMultiplier: 1 }
];

function setupPath() {
    const path = document.querySelector("#path");
    pathLength = path.getTotalLength();
    path.style.strokeDasharray = pathLength;
    path.style.strokeDashoffset = pathLength;
}

function calculateMetrics() {
    const path = document.querySelector("#path");
    pathBoundingBox = path.getBoundingClientRect();
    const offsetFromTop = innerHeight - 400;
    startY = pathBoundingBox.top - innerHeight + offsetFromTop;
    finishDistance = startY + pathBoundingBox.height - offsetFromTop;
}

function update() {
    const scrollPosition = window.scrollY;
    const progress = (scrollPosition - startY) / finishDistance;
    const clampedProgress = Math.min(Math.max(progress, 0), 1);
    const pathPercentage = (scrollPosition - startY) / (finishDistance - startY);
    let currentSegment = null;
    for (let i = 0; i < pathSegments.length; i++) {
        const segment = pathSegments[i];
        if (pathPercentage >= segment.start && pathPercentage <= segment.end) {
            currentSegment = segment;
            break;
        }
    }
    let pathMultiplier = 1;
    let arrowMultiplier = 1;
    if (currentSegment) {
        const segmentProgress = (pathPercentage - currentSegment.start) / (currentSegment.end - currentSegment.start);
        pathMultiplier = currentSegment.pathMultiplier + (segmentProgress * (currentSegment.pathMultiplier - 1)); // Adjust path multiplier
        arrowMultiplier = currentSegment.arrowMultiplier + (segmentProgress * (currentSegment.arrowMultiplier - 1)); // Adjust arrow multiplier
    }
    pathSpeed = pathMultiplier * 0.1; 
    const strokeOffset = pathLength * (1 - clampedProgress +0.005);
    gsap.to("#path", { strokeDashoffset: strokeOffset, duration: pathSpeed });
    arrowTween.progress(clampedProgress * arrowMultiplier);
    arrowSpeed = arrowMultiplier; 
    requestId = null;
}
const arrowTween = gsap.to("#arrow", {
    duration: arrowSpeed, 
    paused: true,
    ease: "none",
    motionPath: {
        path: "#path",
        align: "#path",
        autoRotate: true,
        alignOrigin: [0.5, 0.5],
    },
    onUpdate: () => {
        const rotationAdjustment = 40; 
        const currentRotation = gsap.getProperty("#arrow", "rotation");
        gsap.set("#arrow", { rotation: currentRotation + rotationAdjustment });
    },
}).pause(0.001);

window.addEventListener("resize", () => {
    calculateMetrics();
    update();
});

let requestId = null;
document.addEventListener("scroll", () => {
    if (!requestId) {
        requestId = requestAnimationFrame(update);
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const path = document.querySelector("#path");
    const header = document.querySelector(".header_middle");
    const goal1 = document.querySelector("#goal1");
    const goal2 = document.querySelector("#goal2");
    const goal3 = document.querySelector("#goal3");
    const plusToDot1 = document.querySelector("#plusToDot1");
    const plusToDot2 = document.querySelector("#plusToDot2");
    const plusToDot3 = document.querySelector("#plusToDot3");
    const plusToDot4 = document.querySelector("#plusToDot4");

    function positionGoalOnPath(goal, targetDistance, extraTop, extraLeft) {
        const point = path.getPointAtLength(targetDistance);

        if (goal) {
            goal.style.position = "absolute";
            goal.style.left = `${100 + extraLeft}px`;
            goal.style.top = `${point.y + extraTop}px`;
            goal.style.transform = "translate(-50%, -50%)";
        }
    }

    function adjustForScreenSize() {
        const width = window.innerWidth;
        let extraTop1, extraLeft1, extraTop2, extraLeft2, extraTop3, extraLeft3, extraTop4, extraLeft4;
        let plusToDot1Top,plusToDot1Left;
        let plusToDot2Top,plusToDot2Left;
        let plusToDot3Top,plusToDot3Left;
        let plusToDot4Top,plusToDot4Left;

        if (width < 650 && width > 550) {
            extraTop1 = -160; extraLeft1 = 120;
            extraTop2 = 0; extraLeft2 = -20;
            extraTop3 = 600; extraLeft3 = 150;
            extraTop4 = 1060; extraLeft4 = -20;
            plusToDot1Top=200;plusToDot1Left=0;
            plusToDot2Top=0;plusToDot2Left=300;
            plusToDot3Top=1050;plusToDot3Left=300;
            plusToDot4Top=280;plusToDot4Left=120;
        } else if (width < 769) {
            extraTop1 = -150; extraLeft1 = 180;
            extraTop2 = 0; extraLeft2 = -20;
            extraTop3 = 810; extraLeft3 = 80;
            extraTop4 = 1260; extraLeft4 = -20;
            plusToDot1Top=600;plusToDot1Left=0;
            plusToDot2Top=0;plusToDot2Left=500;
            plusToDot3Top=700;plusToDot3Left=500;
            plusToDot4Top=380;plusToDot4Left=120;
        } else if (width < 899) {
            extraTop1 = -110; extraLeft1 = 230;
            extraTop2 = 40; extraLeft2 = 40;
            extraTop3 = 240; extraLeft3 = -10;
            extraTop4 = 460; extraLeft4 = 50;
            plusToDot1Top=20;plusToDot1Left=-40;
            plusToDot2Top=70;plusToDot2Left=640;
            plusToDot3Top=300;plusToDot3Left=500;
            plusToDot4Top=380;plusToDot4Left=-60;
        } else if (width < 1024) {
            extraTop1 = -80; extraLeft1 = 270;
            extraTop2 = 80; extraLeft2 = 140;
            extraTop3 = 340; extraLeft3 = -10;
            extraTop4 = 600; extraLeft4 = 30;
            plusToDot1Top=50;plusToDot1Left=-30;
            plusToDot2Top=150;plusToDot2Left=770;
            plusToDot3Top=400;plusToDot3Left=700;
            plusToDot4Top=490;plusToDot4Left=-40;
        } else if (width < 1150) {
            extraTop1 = -80; extraLeft1 = 370;
            extraTop2 = 80; extraLeft2 = 70;
            extraTop3 = 320; extraLeft3 = -20;
            extraTop4 = 560; extraLeft4 = 0;
            plusToDot1Top=50;plusToDot1Left=-30;
            plusToDot2Top=150;plusToDot2Left=770;
            plusToDot3Top=400;plusToDot3Left=700;
            plusToDot4Top=490;plusToDot4Left=-40;
        } else if (width < 1400) {
            extraTop1 = -50; extraLeft1 = 400;
            extraTop2 = 160; extraLeft2 = 100;
            extraTop4 = 700; extraLeft4 = 40;
            extraTop3 = 420; extraLeft3 = -20;
            plusToDot1Top=200;plusToDot1Left=-10;
            plusToDot2Top=150;plusToDot2Left=1030;
            plusToDot3Top=400;plusToDot3Left=800;
            plusToDot4Top=660;plusToDot4Left=-20;
        } else if (width > 1400) {
            extraTop1 = 20; extraLeft1 = 530;
            extraTop2 = 200; extraLeft2 = 230;
            extraTop4 = 700; extraLeft4 = 100;
            extraTop3 = 460; extraLeft3 = -20;
            plusToDot1Top=200;plusToDot1Left=100;
            plusToDot2Top=150;plusToDot2Left=1230;
            plusToDot3Top=400;plusToDot3Left=900;
            plusToDot4Top=700;plusToDot4Left=-10;
        }

        positionGoalOnPath(header, path.getTotalLength() * 0.1, extraTop1, extraLeft1);
        positionGoalOnPath(goal1, path.getTotalLength() * 0.1, extraTop2, extraLeft2);
        positionGoalOnPath(goal2, path.getTotalLength() * 0.1, extraTop3, extraLeft3);
        positionGoalOnPath(goal3, path.getTotalLength() * 0.1, extraTop4, extraLeft4);
        positionGoalOnPath(plusToDot1, path.getTotalLength() * 0.1, plusToDot1Top, plusToDot1Left);
        positionGoalOnPath(plusToDot2, path.getTotalLength() * 0.1, plusToDot2Top, plusToDot2Left);
        positionGoalOnPath(plusToDot3, path.getTotalLength() * 0.1, plusToDot3Top, plusToDot3Left);
        positionGoalOnPath(plusToDot4, path.getTotalLength() * 0.1, plusToDot4Top, plusToDot4Left);
    }
    adjustForScreenSize();
    window.addEventListener("resize", adjustForScreenSize);
});
gsap.registerPlugin(ScrollTrigger);

document.addEventListener("DOMContentLoaded", () => {
    
    gsap.utils.toArray('.goal').forEach((goal, index) => {
        if(index==0)delayValue=1;
        else if(index==1)delayValue=1.5;
        else delayValue=1.1;
        gsap.from(goal, {
            scrollTrigger: {
                trigger: goal,       
                start: "top 130%",   
                toggleActions: "play none none reverse", 
            },
            x: 600,                
            opacity: 0,            
            duration: 1,          
            delay: index * delayValue,    
        });
        
    });

    setupPath();
    calculateMetrics();
    update();

    window.addEventListener("resize", () => {
        calculateMetrics();
        update();
    });
});
(function () {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach((item) => {
        const question = item.querySelector('.faq-question'); 
        const answer = item.querySelector('.faq-answer'); 

        question.addEventListener('click', () => {
            question.classList.toggle('active');
            if (question.classList.contains('active')) {
                gsap.to(answer, {
                    duration: 0.4, 
                    opacity: 1, 
                    height: "auto",  
                    paddingTop: 15,  
                    paddingBottom: 15,
                    transform: "translateY(0)", 
                    ease: "power2.out",
                });
            } else {
                gsap.to(answer, {
                    duration: 0.3, 
                    opacity: 0, 
                    height: 0, 
                    paddingTop: 0,  
                    paddingBottom: 0,
                    transform: "translateY(-20px)",
                    ease: "power2.in",
                });
            }
        });
    });
})();


