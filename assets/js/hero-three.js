// Steven Picknwear — homepage hero only. A light abstract particle field
// in brand tones. Bails out
// cleanly on prefers-reduced-motion, small/low-power devices, or if
// Three.js failed to load — the CSS gradient behind the canvas always
// carries the hero on its own.
(function () {
  const canvas = document.getElementById('heroCanvas');
  if (!canvas) return;

  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced || typeof THREE === 'undefined') {
    canvas.style.display = 'none';
    return;
  }

  const isNarrow = window.innerWidth < 768;
  const lowPower = (navigator.hardwareConcurrency || 4) < 4;
  const particleCount = isNarrow || lowPower ? 200 : 600;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(60, canvas.clientWidth / canvas.clientHeight, 0.1, 100);
  camera.position.z = 18;

  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  } catch (e) {
    canvas.style.display = 'none';
    return;
  }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isNarrow ? 1.5 : 2));
  renderer.setSize(canvas.clientWidth, canvas.clientHeight, false);

  // Brand palette (normalized 0-1): saddle, saddle-light, cream, espresso-2
  const palette = [
    [0.663, 0.443, 0.239],
    [0.788, 0.592, 0.376],
    [0.945, 0.906, 0.855],
    [0.239, 0.157, 0.094],
  ];

  const positions = new Float32Array(particleCount * 3);
  const colors = new Float32Array(particleCount * 3);
  const sizes = new Float32Array(particleCount);

  for (let i = 0; i < particleCount; i++) {
    positions[i * 3]     = (Math.random() - 0.5) * 40;
    positions[i * 3 + 1] = (Math.random() - 0.5) * 24;
    positions[i * 3 + 2] = (Math.random() - 0.5) * 20;

    const c = palette[Math.floor(Math.random() * palette.length)];
    colors[i * 3] = c[0]; colors[i * 3 + 1] = c[1]; colors[i * 3 + 2] = c[2];
    sizes[i] = Math.random() * 0.22 + 0.06;
  }

  const geometry = new THREE.BufferGeometry();
  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
  geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
  geometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));

  const material = new THREE.PointsMaterial({
    size: 0.5,
    vertexColors: true,
    transparent: true,
    opacity: 0.85,
    sizeAttenuation: true,
    depthWrite: false,
  });

  const points = new THREE.Points(geometry, material);
  scene.add(points);

  // A few larger soft discs drifting behind the particles
  const patchGeo = new THREE.CircleGeometry(1, 24);
  const patches = [];
  const patchCount = isNarrow ? 3 : 6;
  for (let i = 0; i < patchCount; i++) {
    const c = palette[Math.floor(Math.random() * palette.length)];
    const mat = new THREE.MeshBasicMaterial({ color: new THREE.Color(c[0], c[1], c[2]), transparent: true, opacity: 0.08 });
    const mesh = new THREE.Mesh(patchGeo, mat);
    mesh.scale.setScalar(Math.random() * 5 + 3);
    mesh.position.set((Math.random() - 0.5) * 34, (Math.random() - 0.5) * 20, (Math.random() - 0.5) * 15 - 5);
    scene.add(mesh);
    patches.push(mesh);
  }

  let mouseX = 0, mouseY = 0;
  window.addEventListener('pointermove', (e) => {
    mouseX = (e.clientX / window.innerWidth - 0.5);
    mouseY = (e.clientY / window.innerHeight - 0.5);
  }, { passive: true });

  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      camera.aspect = canvas.clientWidth / canvas.clientHeight;
      camera.updateProjectionMatrix();
      renderer.setSize(canvas.clientWidth, canvas.clientHeight, false);
    }, 150);
  });

  let raf;
  let visible = true;
  document.addEventListener('visibilitychange', () => { visible = !document.hidden; });

  function animate() {
    raf = requestAnimationFrame(animate);
    if (!visible) return;

    points.rotation.y += 0.0009;
    points.rotation.x += 0.0002;
    patches.forEach((p, i) => { p.rotation.z += 0.0006 * (i % 2 === 0 ? 1 : -1); });

    camera.position.x += (mouseX * 3 - camera.position.x) * 0.02;
    camera.position.y += (-mouseY * 2 - camera.position.y) * 0.02;
    camera.lookAt(scene.position);

    renderer.render(scene, camera);
  }
  animate();

  window.addEventListener('pagehide', () => cancelAnimationFrame(raf));
})();
