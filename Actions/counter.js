const countUp = document.getElementById('count-up');
let count = 0;

countUp.onclick = function (e) {
    e.preventDefault();
    count++;
    countUp.textContent = count;
};
