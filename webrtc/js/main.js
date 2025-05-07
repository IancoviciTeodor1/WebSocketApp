'use strict';


const $self = {
  rtcConfig: null,
  mediaConstraints: { audio: true, video: true },
  mediaDevices: { audioinput: [], videoinput: [] },
  mediaStream: new MediaStream(),
  mediaTracks: {},
  features: {
    audio: false,
    video: true,
  },
};

const $peers = new Map();


const namespace = prepareNamespace(window.location.hash, true);

const sc = io.connect('/' + namespace, { autoConnect: false });

registerScCallbacks();





document.querySelector('#toggle-mic')
  .setAttribute('aria-checked', $self.features.audio);

document.querySelector('#call-button')
  .addEventListener('click', handleCallButton);

document.querySelector('#footer')
  .addEventListener('click', handleMediaButtons);


requestUserMedia($self.mediaConstraints);

navigator.mediaDevices.ondevicechange = debounce(handleMediaDeviceChange, 500);



function handleCallButton(event) {
  const call_button = event.target;
  if (call_button.className === 'join') {
    console.log('Joining the call...');
    call_button.className = 'leave';
    call_button.innerText = 'Leave Call';
    joinCall();
  } else {
    console.log('Leaving the call...');
    call_button.className = 'join';
    call_button.innerText = 'Join Call';
    leaveCall();
  }
}

function joinCall() {
  sc.open();
}

function leaveCall() {
  sc.close();
  for (let id of $peers.keys()) {
    resetPeer(id);
  }
}

function handleMediaButtons(event) {
  const target = event.target;
  if (target.tagName !== 'BUTTON') return;
  switch (target.id) {
  case 'toggle-mic':
    toggleMic(target);
    break;
  case 'toggle-cam':
    toggleCam(target);
    break;
  }
}

function toggleMic(button) {
  const audio = $self.mediaTracks.audio;
  const enabled_state = audio.enabled = !audio.enabled;

  $self.features.audio = enabled_state;

  button.setAttribute('aria-checked', enabled_state);

  for (let id of $peers.keys()) {
    shareFeatures(id, 'audio');
  }
}

function toggleCam(button) {
  const video = $self.mediaTracks.video;
  const enabled_state = video.enabled = !video.enabled;

  $self.features.video = enabled_state;

  button.setAttribute('aria-checked', enabled_state);

  for (let id of $peers.keys()) {
    shareFeatures(id, 'video');
  }

  if (enabled_state) {
    $self.mediaStream.addTrack($self.mediaTracks.video);
  } else {
    $self.mediaStream.removeTrack($self.mediaTracks.video);
    displayStream($self.mediaStream);
  }
}

function enableOrDisableMediaToggleButtons() {
  const audio_button = document.querySelector('#toggle-mic');
  const video_button = document.querySelector('#toggle-cam');

  audio_button.disabled = $self.mediaDevices.audioinput.length === 0;
  video_button.disabled = $self.mediaDevices.videoinput.length === 0;
}


const username = new URLSearchParams(window.location.hash.split('?')[1]).get('user');
$self.features.username = username;
document.querySelector('#self figcaption').innerText = username;
for (let id of $peers.keys()) {
  shareFeatures(id, 'username');
}
console.log(username);



async function detectAvailableMediaDevices() {
  $self.mediaDevices.audioinput = [];
  $self.mediaDevices.videoinput = [];

  const devices = await navigator.mediaDevices.enumerateDevices();

  for (let device of devices) {
    const input_kinds = ['audioinput', 'videoinput'];
    if (input_kinds.includes(device.kind)) {
      $self.mediaDevices[device.kind].push(device);
    }
  }
}

async function handleMediaDeviceChange() {
  const previous_devices =
    $self.mediaDevices.audioinput.length > 0
      || $self.mediaDevices.videoinput.length > 0;

  await detectAvailableMediaDevices();

  const available_devices =
    $self.mediaDevices.audioinput.length > 0
      || $self.mediaDevices.videoinput.length > 0;

  if (!previous_devices && available_devices) {
    await requestUserMedia($self.mediaConstraints);

    for (let id of $peers.keys()) {
      addStreamingMedia(id);
      shareFeatures(id, 'audio', 'video');
    }
  }

  if (!available_devices) {
    $self.media = false;
    $self.mediaTracks = {};
    $self.mediaStream = new MediaStream();

    enableOrDisableMediaToggleButtons();
    displayStream(null);

    for (let id of $peers.keys()) {
      removeStreamingMedia(id);
    }
  }
}

async function requestUserMedia(media_constraints) {
  const refined_media_constraints =
    JSON.parse(JSON.stringify(media_constraints));

  await detectAvailableMediaDevices();

  if ($self.mediaDevices.audioinput.length === 0) {
    refined_media_constraints.audio = false;
  }
  if ($self.mediaDevices.videoinput.length === 0) {
    refined_media_constraints.video = false;
  }


  enableOrDisableMediaToggleButtons();

  if (!refined_media_constraints.audio &&
        !refined_media_constraints.video) {
    return;
  }

  try {
    $self.media = await navigator.mediaDevices
      .getUserMedia(refined_media_constraints);
    await detectAvailableMediaDevices();
  } catch(e) {
    console.error(e.name, e.message);
  }


  $self.mediaTracks.audio = $self.media.getAudioTracks()[0];
  $self.mediaTracks.video = $self.media.getVideoTracks()[0];

  if ($self.mediaTracks.audio) {
    $self.mediaTracks.audio.enabled = !!$self.features.audio;
    $self.mediaStream.addTrack($self.mediaTracks.audio);
  }
  if ($self.mediaTracks.video) {
    $self.mediaTracks.video.enabled = !!$self.features.video;
    $self.mediaStream.addTrack($self.mediaTracks.video);
  }

  displayStream($self.mediaStream);
}

function createVideoStructure(id) {
  const figure = document.createElement('figure');
  const figcaption = document.createElement('figcaption');
  const video = document.createElement('video');
  const attributes = {
    autoplay: '',
    playsinline: '',
    poster: '../img/placeholder.png',
  };
  const attributes_list = Object.keys(attributes);

  figure.id = `peer-${id}`;
  figcaption.innerText = id;
  for (let attr of attributes_list) {
    video.setAttribute(attr, attributes[attr]);
  }

  figure.appendChild(video);
  figure.appendChild(figcaption);

  return figure;
}

function displayStream(stream, id = 'self') {
  const selector = id === 'self' ? '#self' : `#peer-${id}`;
  let video_structure = document.querySelector(selector);
  if (!video_structure) {
    const videos = document.querySelector('#videos');
    video_structure = createVideoStructure(id);
    videos.appendChild(video_structure);
  }
  video_structure.querySelector('video').srcObject = stream;
}

function addStreamingMedia(id) {
  const peer = $peers.get(id);
  const tracks = Object.keys($self.mediaTracks);
  for (let track of tracks) {
    if ($self.mediaTracks[track]) {
      peer.connection.addTrack($self.mediaTracks[track]);
    }
  }
}

function removeStreamingMedia(id) {
  const peer = $peers.get(id);
  const senders = peer.connection.getSenders();
  const senders_list = Object.keys(senders);

  for (let sender of senders_list) {
    const track = senders[sender].track;
    if (track) {
      peer.connection.removeTrack(senders[sender]);
    }
  }
  
  shareFeatures(id, 'removeAllTracks');
}

function addFeaturesChannel(id) {
  const peer = $peers.get(id);

  const featureFunctions = {
    audio: function() {
      const username = peer.features.username ? peer.features.username : id;
      showUsernameAndMuteStatus(username);
    },
    removeAllTracks: function() {
      const tracks_list = Object.keys(peer.mediaTracks);
      for (let track of tracks_list) {
        peer.mediaStream.removeTrack(peer.mediaTracks[track]);
      }

      peer.mediaTracks = {};
      
      peer.mediaStream = new MediaStream();
      
      displayStream(null, id);
    },


    username: function() {
      showUsernameAndMuteStatus(peer.features.username);
    },
    video: function() {
      if (peer.mediaTracks.video) {
        if (peer.features.video) {
          peer.mediaStream.addTrack(peer.mediaTracks.video);
        } else {
          peer.mediaStream.removeTrack(peer.mediaTracks.video);
          displayStream(peer.mediaStream, id);
        }
      }
    },
  };


  peer.featuresChannel =
    peer.connection.createDataChannel('features',
      { negotiated: true, id: 110 });

  peer.featuresChannel.onopen = function() {
    peer.featuresChannel.send(JSON.stringify($self.features));
  };

  peer.featuresChannel.onmessage = function(event) {
    const features = JSON.parse(event.data);
    const features_list = Object.keys(features);
    for (let f of features_list) {
      peer.features[f] = features[f];
      if (typeof featureFunctions[f] === 'function') {
        featureFunctions[f]();
      }
    }
  };

  function showUsernameAndMuteStatus(username) {
    const fc = document.querySelector(`#peer-${id} figcaption`);
    if (peer.features.audio) {
      fc.innerText = username;
    } else {
      fc.innerText = `${username} (Muted)`;
    }
  }
}


function shareFeatures(id, ...features) {
  const peer = $peers.get(id);

  const featuresToShare = {};

  if (!peer.featuresChannel) return;

  for (let f of features) {
    featuresToShare[f] =
      $self.features[f] ? $self.features[f] : 'true';
  }

  try {
    peer.featuresChannel.send(JSON.stringify(featuresToShare));
  } catch(e) {
    console.error('Error sending features:', e);
  }
}



function initializePeer(id, polite) {
  $peers.set(id, {
    connection: new RTCPeerConnection($self.rtcConfig),
    mediaStream: new MediaStream(),
    mediaTracks: {},
    features: {},
    selfStates: {
      isPolite: polite,
      isMakingOffer: false,
      isIgnoringOffer: false,
      isSettingRemoteAnswerPending: false,
    },
  });
}

function establishCallFeatures(id) {
  registerRtcCallbacks(id);
  addFeaturesChannel(id);
  displayStream(null, id);
  addStreamingMedia(id);
}

function resetPeer(id) {
  const peer = $peers.get(id);
  displayStream(null, id);
  document.querySelector(`#peer-${id}`).remove();
  peer.connection.close();
  $peers.delete(id);
}



function registerRtcCallbacks(id) {
  const peer = $peers.get(id);
  peer.connection
    .onconnectionstatechange = handleRtcConnectionStateChange(id);
  peer.connection
    .onnegotiationneeded = handleRtcConnectionNegotiation(id);
  peer.connection
    .onicecandidate = handleRtcIceCandidate(id);
  peer.connection
    .ontrack = handleRtcPeerTrack(id);
}

function handleRtcPeerTrack(id) {
  return function({ track }) {
    const peer = $peers.get(id);
    console.log(`Handle incoming ${track.kind} track from peer ID: ${id}`);
    peer.mediaTracks[track.kind] = track;
    peer.mediaStream.addTrack(track);
    displayStream(peer.mediaStream, id);
  };
}



function handleRtcConnectionNegotiation(id) {
  return async function() {
    const peer = $peers.get(id);
    const self_state = peer.selfStates;
    self_state.isMakingOffer = true;
    await peer.connection.setLocalDescription();
    sc.emit('signal',
      { recipient: id, sender: $self.id,
        signal: { description: peer.connection.localDescription } });
    self_state.isMakingOffer = false;
  };
}

function handleRtcIceCandidate(id) {
  return function({ candidate }) {
    sc.emit('signal', { recipient: id, sender: $self.id,
      signal: { candidate } });
  };
}

function handleRtcConnectionStateChange(id) {
  return function() {
    const peer = $peers.get(id);
    const connection_state = peer.connection.connectionState;
    const peer_element = document.querySelector(`#peer-${id}`);
    if (peer_element) {
      peer_element.dataset.connectionState = connection_state;
    }
    console.log(`Connection state '${connection_state}' for Peer ID: ${id}`);
  };
}



function registerScCallbacks() {
  sc.on('connect', handleScConnect);
  sc.on('connected peers', handleScConnectedPeers);
  sc.on('connected peer', handleScConnectedPeer);
  sc.on('disconnected peer', handleScDisconnectedPeer);
  sc.on('signal', handleScSignal);
}

function handleScConnect() {
  console.log('Successfully connected to the signaling server!');
  $self.id = sc.id;
  console.log(`Self ID: ${$self.id}`);
}

function handleScConnectedPeers(ids) {
  console.log(`Connected peer IDs: ${ids.join(', ')}`);
  for (let id of ids) {
    if (id === $self.id) continue;
    initializePeer(id, true);
    establishCallFeatures(id);
  }
}

function handleScConnectedPeer(id) {
  console.log(`Newly connected peer ID: ${id}`);
  initializePeer(id, false);
  establishCallFeatures(id);
}

function handleScDisconnectedPeer(id) {
  console.log(`Disconnected peer ID: ${id}`);
  resetPeer(id);
}

async function handleScSignal({ sender,
  signal: { candidate, description } }) {

  const id = sender;
  const peer = $peers.get(id);
  const self_state = peer.selfStates;

  if (description) {
    const ready_for_offer =
          !self_state.isMakingOffer &&
          (peer.connection.signalingState === 'stable'
            || self_state.isSettingRemoteAnswerPending);

    const offer_collision =
          description.type === 'offer' && !ready_for_offer;

    self_state.isIgnoringOffer = !self_state.isPolite && offer_collision;

    if (self_state.isIgnoringOffer) {
      return;
    }

    self_state.isSettingRemoteAnswerPending = description.type === 'answer';

    await peer.connection.setRemoteDescription(description);

    self_state.isSettingRemoteAnswerPending = false;

    if (description.type === 'offer') {
      await peer.connection.setLocalDescription();
      sc.emit('signal', { recipient: id, sender: $self.id,
        signal: { description: peer.connection.localDescription } });
    }

  } else if (candidate) {
    try {
      await peer.connection.addIceCandidate(candidate);
    } catch(e) {
      if (!self_state.isIgnoringOffer && candidate.candidate.length > 1) {
        console.error(`Unable to add ICE candidate for peer ID: ${id}.`, e);
      }
    }
  }
}



function prepareNamespace(hash, set_location) {
  let ns = hash.replace(/^#/, '');

  return ns;
}

function debounce(callback_function, wait_in_milliseconds) {
  let timeout;
  return (...args) => {
    const context = this;
    clearTimeout(timeout);
    timeout = setTimeout(
      () => callback_function.apply(context, args),
      wait_in_milliseconds);
  };
}
