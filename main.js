const musicContainer = document.querySelector('.music-container');

const playBtn = document.querySelector('#play');
const prevBtn = document.querySelector('#prev');
const nextBtn = document.querySelector('#next');

const audio = document.querySelector('#audio');

const progress = document.querySelector('.progress');
const progressContainer = document.querySelector('.progress-container');

const title = document.querySelector('#title');
const cover = document.querySelector('#cover');

const volumeContainer = document.querySelector('.volume-container');
const volume = document.querySelector('#volume');

// Список песе

let songIndex = 0;
let volumeIsClick = false;
let currentVolume = 0.5;
let songs = [];

getSongsFromServer(addSongsToArray);

function getSongsFromServer(callback) {
    $.get('http://localhost/yobaradio/server/main.php').done(
        function (data) {
            callback(JSON.parse(data));
        }
    );
}

function addSongsToArray(arr) {
    songs = arr;
    loadSong(songs['audio'][songIndex], songs['images'][songIndex]);
    setDefaultVolume();
}

function loadSong(audio_src, image_src) {
    title.innerText = getTitle(audio_src);
    audio.src = 'http://localhost/yobaradio/music/' + audio_src;
    cover.src = 'http://localhost/yobaradio/images/' + image_src;
}

function getTitle(audio_src) {
    let endOfStr = audio_src.length - 1;
    let title = audio_src.substring(0, endOfStr-3);

    return title;
}

function playSong() {
    musicContainer.classList.add('play');
    playBtn.querySelector('i.fas').classList.remove('fa-play');
    playBtn.querySelector('i.fas').classList.add('fa-pause');

    audio.play();
}

function pauseSong() {
    musicContainer.classList.remove('play');
    playBtn.querySelector('i.fas').classList.remove('fa-pause');
    playBtn.querySelector('i.fas').classList.add('fa-play');

    audio.pause();
}

function prevSong() {
    songIndex--;

    if (songIndex < 0) {
        songIndex = songs['audio'].length - 1;
    }

    loadSong(songs['audio'][songIndex], songs['images'][songIndex]);
    playSong();
}

function nextSong() {
    songIndex++;

    if (songIndex > songs['audio'].length - 1) {
        songIndex = 0;
    }

    loadSong(songs['audio'][songIndex], songs['images'][songIndex]);
    playSong();
}

function updateProgress(e) {
    const {duration, currentTime} = e.srcElement;
    const progressPercent = (currentTime / duration) * 100;
    progress.style.width = progressPercent + '%';
}

function setProgress(e) {
    const width = this.clientWidth;
    const clickX = e.offsetX;
    const duration = audio.duration;

    audio.currentTime = (clickX / width) * duration;
}

function volumeMouseUp() {
    volumeIsClick = false;
}

function volumeMouseDown() {
    volumeIsClick = true;
}

function setDefaultVolume() {
    audio.volume = currentVolume;
    volume.style.width = (currentVolume / 1 * 100) + '%';
    
}

function setVolume(e) {
    if (volumeIsClick === true) {
        const width = this.clientWidth;
        const clickX = e.offsetX;
        currentVolume = clickX / width;
        if (currentVolume > 1) currentVolume = 1;
        if (currentVolume < 0) currentVolume = 0;
        audio.volume = currentVolume;
        volume.style.width = (audio.volume * 100) + '%';
    }
}

// Listeners

playBtn.addEventListener('click', () => {
    const isPlaying = musicContainer.classList.contains('play');

    if (isPlaying) {
        pauseSong();
    } else {
        playSong();
    }
});

prevBtn.addEventListener('click', prevSong);

nextBtn.addEventListener('click', nextSong);

audio.addEventListener('timeupdate', updateProgress);
audio.addEventListener('ended', nextSong);

progressContainer.addEventListener('click', setProgress);

volumeContainer.addEventListener('mousemove', setVolume);
volumeContainer.addEventListener('mouseup', volumeMouseUp);
volumeContainer.addEventListener('mousedown', volumeMouseDown);