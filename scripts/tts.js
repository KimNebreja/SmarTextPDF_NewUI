document.addEventListener('DOMContentLoaded', () => {
    const playBtn = document.getElementById('ttsPlayBtn');
    const pauseBtn = document.getElementById('ttsPauseBtn');
    const stopBtn = document.getElementById('ttsStopBtn');
    const speedSlider = document.getElementById('ttsSpeed');
    const speedValue = document.getElementById('speedValue');
    const voiceSelect = document.getElementById('ttsVoice');
    const progressBar = document.getElementById('ttsProgress');
    const currentTimeEl = document.getElementById('currentTime');
    const totalTimeEl = document.getElementById('totalTime');
    const revisedTextEl = document.getElementById('revisedText');

    let speechSynthesis = window.speechSynthesis;
    let currentUtterance = null;
    let isPlaying = false;
    let startTime = 0;
    let totalDuration = 0;
    let progressInterval = null;
    let highlightInterval = null;
    let wordTimings = [];
    let currentWordIndex = 0;
    let originalTextHTML = '';

    if(revisedTextEl) {
       originalTextHTML = revisedTextEl.innerHTML;
    }

    function loadVoices() {
        const voices = speechSynthesis.getVoices();
        if (voiceSelect) {
            voiceSelect.innerHTML = '';
            voices.forEach(voice => {
                const option = document.createElement('option');
                option.value = voice.name;
                option.textContent = `${voice.name} (${voice.lang})`;
                voiceSelect.appendChild(option);
            });
        }
    }

    if (speechSynthesis.onvoiceschanged !== undefined) {
        speechSynthesis.onvoiceschanged = loadVoices;
    }
    if (speechSynthesis.getVoices().length > 0) {
        loadVoices();
    }

    function calculateWordTimings(text, rate) {
        const words = text.split(/\s+/).filter(word => word.length > 0);
        const baseTimePerWord = 200;
        const timings = [];
        let currentTime = 0;

        words.forEach(word => {
            const wordDuration = (word.replace(/[.,!?;:]/g, '').length * baseTimePerWord) / rate;
            timings.push({
                word: word.replace(/[.,!?;:]/g, ''),
                startTime: currentTime,
                endTime: currentTime + wordDuration
            });
            currentTime += wordDuration;
        });

        return timings;
    }

    function updateWordHighlight() {
        if (!revisedTextEl || currentWordIndex >= wordTimings.length || !isPlaying) return;

        const elapsed = Date.now() - startTime;
        const currentTiming = wordTimings[currentWordIndex];

        if (elapsed >= currentTiming.startTime) {
             const previousHighlight = revisedTextEl.querySelector('.word-highlight');
             if (previousHighlight) {
                 const parent = previousHighlight.parentNode;
                 while (previousHighlight.firstChild) {
                     parent.insertBefore(previousHighlight.firstChild, previousHighlight);
                 }
                 parent.removeChild(previousHighlight);
             }

             if (elapsed > currentTiming.endTime && currentWordIndex < wordTimings.length -1) {
                  currentWordIndex++;
                  updateWordHighlight();
                  return;
             }

            const textNodes = getTextNodes(revisedTextEl);
            let charIndex = 0;
            let wordFound = false;

            for (const node of textNodes) {
                 const wordsInNode = node.textContent.split(/\s+/);
                 let wordNodeIndex = 0;

                 for (const word of wordsInNode) {
                      if (charIndex + word.length >= getWordStartIndex(wordTimings, currentWordIndex)) {
                          const range = document.createRange();
                          const startIndex = node.textContent.indexOf(word, wordNodeIndex);

                          if (startIndex !== -1) {
                               range.setStart(node, startIndex);
                               range.setEnd(node, startIndex + word.length);

                               const highlightSpan = document.createElement('span');
                               highlightSpan.classList.add('word-highlight');
                               highlightSpan.textContent = word;

                               try {
                                   range.deleteContents();
                                   range.insertNode(highlightSpan);
                                   wordFound = true;
                                   break;
                               } catch (e) {
                                   console.error("Highlighting error:", e);
                                   wordFound = true;
                                   break;
                               }
                          }
                      }
                      charIndex += word.length + 1;
                      wordNodeIndex++;
                 }
                 if (wordFound) break;
            }
        }
    }

    function getTextNodes(node) {
        let textNodes = [];
        if (node.nodeType === Node.TEXT_NODE) {
            textNodes.push(node);
        } else {
            for (const childNode of node.childNodes) {
                textNodes = textNodes.concat(getTextNodes(childNode));
            }
        }
        return textNodes;
    }

    function getWordStartIndex(timings, index) {
         let startIndex = 0;
         for(let i = 0; i < index; i++) {
             startIndex += timings[i].word.length + 1;
         }
         return startIndex;
    }

    if (speedSlider && speedValue) {
        speedSlider.addEventListener('input', (e) => {
            try {
                const speed = parseFloat(e.target.value);
                speedValue.textContent = `${speed}x`;
                if (currentUtterance) {
                    currentUtterance.rate = speed;
                    wordTimings = calculateWordTimings(revisedTextEl.textContent, speed);
                }
            } catch (e) {
                console.error("Speed slider error:", e);
            }
        });
    }

    if (playBtn && revisedTextEl && speedSlider && voiceSelect && progressBar && currentTimeEl && totalTimeEl) {
        playBtn.addEventListener('click', () => {
            if (!isPlaying) {
                try {
                    const text = revisedTextEl.textContent;
                    const speed = parseFloat(speedSlider.value);

                    wordTimings = calculateWordTimings(text, speed);
                    currentWordIndex = 0;

                    if (wordTimings.length === 0) {
                        console.warn("No words to speak.");
                        return;
                    }

                    currentUtterance = new SpeechSynthesisUtterance(text);
                    currentUtterance.rate = speed;
                    
                    const selectedVoice = speechSynthesis.getVoices().find(
                        voice => voice.name === voiceSelect.value
                    );
                    if (selectedVoice) {
                         currentUtterance.voice = selectedVoice;
                    } else {
                         console.warn("Selected voice not found, using default.");
                    }

                    startTime = Date.now();
                    totalDuration = wordTimings[wordTimings.length - 1].endTime;
                    
                    resetProgress();

                    if (highlightInterval) clearInterval(highlightInterval);
                    highlightInterval = setInterval(() => {
                         try {
                              updateWordHighlight();
                         } catch(e) {
                              console.error("Error in highlight interval:", e);
                              clearInterval(highlightInterval);
                         }
                    }, 50);

                    if (progressInterval) clearInterval(progressInterval);
                    progressInterval = setInterval(() => {
                         try {
                            const elapsed = Date.now() - startTime;
                            const progress = Math.min((elapsed / totalDuration) * 100, 100);

                            if(progressBar) progressBar.style.width = `${progress}%`;
                            if(currentTimeEl) currentTimeEl.textContent = formatTime(elapsed / 1000);
                            if(totalTimeEl) totalTimeEl.textContent = formatTime(totalDuration / 1000);

                            if (progress >= 100) {
                                clearInterval(progressInterval);
                                clearInterval(highlightInterval);
                                isPlaying = false;
                                updateButtonStates();
                                if(revisedTextEl) revisedTextEl.innerHTML = originalTextHTML;
                            }
                         } catch(e) {
                              console.error("Error in progress interval:", e);
                              clearInterval(progressInterval);
                         }
                    }, 100);

                    speechSynthesis.speak(currentUtterance);
                    isPlaying = true;
                    updateButtonStates();

                    currentUtterance.onend = () => {
                        console.log("Speech finished.");
                        isPlaying = false;
                        clearInterval(progressInterval);
                        clearInterval(highlightInterval);
                        updateButtonStates();
                        resetProgress();
                        if(revisedTextEl) revisedTextEl.innerHTML = originalTextHTML;
                    };

                    currentUtterance.onerror = (event) => {
                        console.error('SpeechSynthesisUtterance error:', event.error);
                        isPlaying = false;
                        clearInterval(progressInterval);
                        clearInterval(highlightInterval);
                        updateButtonStates();
                        resetProgress();
                         if(revisedTextEl) revisedTextEl.innerHTML = originalTextHTML;
                         alert(`Text-to-Speech Error: ${event.error}. Please try again.`);
                    };

                } catch (e) {
                    console.error("Play button click error:", e);
                    isPlaying = false;
                    clearInterval(progressInterval);
                    clearInterval(highlightInterval);
                    updateButtonStates();
                     if(revisedTextEl) revisedTextEl.innerHTML = originalTextHTML;
                    alert("Could not start text-to-speech. Please try again.");
                }
            }
        });
    }

    if (pauseBtn) {
        pauseBtn.addEventListener('click', () => {
            try {
                if (isPlaying) {
                    speechSynthesis.pause();
                    isPlaying = false;
                    clearInterval(progressInterval);
                    clearInterval(highlightInterval);
                    updateButtonStates();
                } else {
                    speechSynthesis.resume();
                    isPlaying = true;
                    startTime = Date.now() - (parseFloat(currentTimeEl.textContent.split(':')[0]) * 60 + parseFloat(currentTimeEl.textContent.split(':')[1])) * 1000;
                    updateProgress();
                    if (highlightInterval) clearInterval(highlightInterval);
                     highlightInterval = setInterval(() => {
                         try {
                              updateWordHighlight();
                         } catch(e) {
                              console.error("Error in highlight interval:", e);
                              clearInterval(highlightInterval);
                         }
                     }, 50);
                    updateButtonStates();
                }
            } catch (e) {
                 console.error("Pause/Resume button click error:", e);
            }
        });
    }

    if (stopBtn && revisedTextEl) {
        stopBtn.addEventListener('click', () => {
            try {
                speechSynthesis.cancel();
                isPlaying = false;
                clearInterval(progressInterval);
                clearInterval(highlightInterval);
                resetProgress();
                updateButtonStates();
                
                revisedTextEl.innerHTML = originalTextHTML;

            } catch (e) {
                console.error("Stop button click error:", e);
            }
        });
    }

    function updateProgress() {
         if (progressInterval) clearInterval(progressInterval);

        progressInterval = setInterval(() => {
             try {
                const elapsed = Date.now() - startTime;
                const progress = Math.min((elapsed / totalDuration) * 100, 100);

                if(progressBar) progressBar.style.width = `${progress}%`;
                if(currentTimeEl) currentTimeEl.textContent = formatTime(elapsed / 1000);
                if(totalTimeEl) totalTimeEl.textContent = formatTime(totalDuration / 1000);

                if (progress >= 100) {
                    clearInterval(progressInterval);
                    isPlaying = false;
                    updateButtonStates();
                }
             } catch(e) {
                 console.error("Error in updateProgress interval:", e);
                 clearInterval(progressInterval);
             }
        }, 100);
    }

    function resetProgress() {
        if(progressBar) progressBar.style.width = '0%';
        if(currentTimeEl) currentTimeEl.textContent = '0:00';
        if(totalTimeEl) totalTimeEl.textContent = '0:00';
        currentWordIndex = 0;
        if(revisedTextEl) revisedTextEl.innerHTML = originalTextHTML;
    }

    function updateButtonStates() {
        if(playBtn) playBtn.disabled = isPlaying;
        if(pauseBtn) pauseBtn.disabled = !isPlaying;
        if(stopBtn) stopBtn.disabled = !isPlaying;
    }

    function formatTime(seconds) {
         if (isNaN(seconds) || seconds < 0) return '0:00';
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }
}); 