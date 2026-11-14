import React, { useEffect, useRef } from 'react';
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

interface ThreeShieldSceneProps {
  onShieldDeflect?: () => void;
}

// Module-level pre-allocated scratch objects to guarantee 0 GC during the 60fps render loop
const _targetEuler = new THREE.Euler();
const _matrix = new THREE.Matrix4();
const _position = new THREE.Vector3();
const _quaternion = new THREE.Quaternion();
const _scale = new THREE.Vector3(1, 1, 1);

export const ThreeShieldScene: React.FC<ThreeShieldSceneProps> = () => {
  const mountRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const container = mountRef.current;
    if (!container) return;

    // Detect software WebGL renderer (SwiftShader, llvmpipe) to adapt rendering cost
    const isSoftwareGl = (() => {
      try {
        const probeCanvas = document.createElement('canvas');
        const gl = probeCanvas.getContext('webgl2') || probeCanvas.getContext('webgl');
        if (!gl) return true;
        const ext = gl.getExtension('WEBGL_debug_renderer_info');
        const param = ext ? ext.UNMASKED_RENDERER_WEBGL : gl.RENDERER;
        const gpu = String(gl.getParameter(param));
        gl.getExtension('WEBGL_lose_context')?.loseContext();
        return /SwiftShader|llvmpipe|softpipe|Software|Basic Render/i.test(gpu);
      } catch {
        return true;
      }
    })();

    const width = container.clientWidth || 560;
    const height = container.clientHeight || 560;

    // 1. Transparent WebGL Renderer — seamless suspension with NO enclosing frame
    const renderer = new THREE.WebGLRenderer({
      antialias: !isSoftwareGl,
      alpha: true,
      powerPreference: 'high-performance',
    });
    renderer.setSize(width, height);
    renderer.setPixelRatio(isSoftwareGl ? 1 : Math.min(window.devicePixelRatio, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.18;
    renderer.shadowMap.enabled = !isSoftwareGl;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;

    container.appendChild(renderer.domElement);

    // 2. Scene & Camera Setup (positioned for grand, larger scale)
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(38, width / height, 0.1, 100);
    camera.position.set(0, 0, 6.6);
    camera.lookAt(0, 0, 0);

    // Procedural Studio Environment Reflection
    const pmremGenerator = new THREE.PMREMGenerator(renderer);
    scene.environment = pmremGenerator.fromScene(new RoomEnvironment(), 0.04).texture;
    pmremGenerator.dispose();

    // 3. Balanced Studio Lighting Rig
    const keyLight = new THREE.DirectionalLight(0xffffff, 2.8);
    keyLight.position.set(4, 5, 4);
    keyLight.castShadow = !isSoftwareGl;
    if (keyLight.castShadow) {
      keyLight.shadow.mapSize.width = 1024;
      keyLight.shadow.mapSize.height = 1024;
      keyLight.shadow.bias = -0.0005;
      keyLight.shadow.normalBias = 0.02;
    }
    scene.add(keyLight);

    const hemiLight = new THREE.HemisphereLight(0xffffff, 0x5B42F3, 1.25);
    scene.add(hemiLight);

    const rimLight = new THREE.DirectionalLight(0xA594FD, 1.8);
    rimLight.position.set(-4, -1, -3);
    scene.add(rimLight);

    // Warm radiant violet-periwinkle kinetic core point light
    const corePointLight = new THREE.PointLight(0x7B68EE, 2.8, 6);
    corePointLight.position.set(0, 0, 0);
    scene.add(corePointLight);

    // Track disposable resources
    const disposables: { dispose: () => void }[] = [];

    // 4. Assemble the Creative Kinetic Cryptographic Defense Core (Bigger scale: 0.72)
    const kineticMasterGroup = new THREE.Group();
    kineticMasterGroup.scale.set(0.72, 0.72, 0.72); // Enlarged 3D figure as requested
    scene.add(kineticMasterGroup);

    // --- Part 1: Outer Dark Indigo Meridian Ring ---
    const outerRingGeo = new THREE.TorusGeometry(2.18, 0.024, 16, 80);
    const outerRingMat = new THREE.MeshStandardMaterial({
      color: 0x1E1B4B,
      metalness: 0.9,
      roughness: 0.22,
    });
    disposables.push(outerRingGeo, outerRingMat);
    const outerRingMesh = new THREE.Mesh(outerRingGeo, outerRingMat);
    kineticMasterGroup.add(outerRingMesh);

    // --- Part 2: Middle Polished Platinum Gyro Ring ---
    const midRingGeo = new THREE.TorusGeometry(1.86, 0.036, 16, 80);
    const midRingMat = new THREE.MeshPhysicalMaterial({
      color: 0xF5F3FF,
      metalness: 0.98,
      roughness: 0.08,
      clearcoat: 0.95,
    });
    disposables.push(midRingGeo, midRingMat);
    const midRingMesh = new THREE.Mesh(midRingGeo, midRingMat);
    midRingMesh.rotation.x = Math.PI / 4;
    kineticMasterGroup.add(midRingMesh);

    // --- Part 3: Inner Vibrant Royal Purple Equatorial Ring ---
    const innerRingGeo = new THREE.TorusGeometry(1.54, 0.044, 16, 80);
    const innerRingMat = new THREE.MeshPhysicalMaterial({
      color: 0x635BFF,
      metalness: 0.88,
      roughness: 0.16,
      clearcoat: 0.8,
    });
    disposables.push(innerRingGeo, innerRingMat);
    const innerRingMesh = new THREE.Mesh(innerRingGeo, innerRingMat);
    innerRingMesh.rotation.y = Math.PI / 3;
    kineticMasterGroup.add(innerRingMesh);

    // --- Part 4: Precision Laser Notches on Equatorial Ring (InstancedMesh) ---
    const notchCount = 18;
    const notchGeo = new THREE.BoxGeometry(0.025, 0.07, 0.04);
    const notchMat = new THREE.MeshStandardMaterial({
      color: 0xF5F3FF,
      metalness: 0.95,
      roughness: 0.1,
      emissive: 0x5B42F3,
    });
    disposables.push(notchGeo, notchMat);
    const notchInstances = new THREE.InstancedMesh(notchGeo, notchMat, notchCount);
    for (let i = 0; i < notchCount; i++) {
      const angle = (i / notchCount) * Math.PI * 2;
      const nx = Math.cos(angle) * 1.54;
      const ny = Math.sin(angle) * 1.54;
      _position.set(nx, ny, 0);
      _targetEuler.set(0, 0, angle + Math.PI / 2);
      _quaternion.setFromEuler(_targetEuler);
      _matrix.compose(_position, _quaternion, _scale);
      notchInstances.setMatrixAt(i, _matrix);
    }
    notchInstances.instanceMatrix.needsUpdate = true;
    innerRingMesh.add(notchInstances);

    // --- Part 5: Central Deep Indigo Polyhedral Prism Core ---
    const coreGeo = new THREE.IcosahedronGeometry(0.85, 0);
    const coreMat = new THREE.MeshPhysicalMaterial({
      color: 0x0F0D24,
      metalness: 0.85,
      roughness: 0.12,
      clearcoat: 1.0,
      clearcoatRoughness: 0.08,
      transparent: true,
      opacity: 0.88,
    });
    disposables.push(coreGeo, coreMat);
    const coreMesh = new THREE.Mesh(coreGeo, coreMat);
    coreMesh.castShadow = !isSoftwareGl;
    coreMesh.receiveShadow = !isSoftwareGl;
    kineticMasterGroup.add(coreMesh);

    // Crisp luminous violet diamond-cut wireframe edges overlay
    const coreEdgesGeo = new THREE.EdgesGeometry(coreGeo);
    const coreEdgesMat = new THREE.LineBasicMaterial({
      color: 0x8B7DFF,
      transparent: true,
      opacity: 0.8,
    });
    disposables.push(coreEdgesGeo, coreEdgesMat);
    const coreEdgesMesh = new THREE.LineSegments(coreEdgesGeo, coreEdgesMat);
    coreMesh.add(coreEdgesMesh);

    // --- Part 6: Nested Glowing Purple Luminescent Heart ---
    const heartGeo = new THREE.OctahedronGeometry(0.5, 0);
    const heartMat = new THREE.MeshStandardMaterial({
      color: 0x635BFF,
      emissive: 0x5B42F3,
      emissiveIntensity: 2.2,
      metalness: 0.8,
      roughness: 0.15,
      wireframe: false,
    });
    disposables.push(heartGeo, heartMat);
    const heartMesh = new THREE.Mesh(heartGeo, heartMat);
    kineticMasterGroup.add(heartMesh);

    // --- Part 7: Planetary Defense Sentinels (Orbiting Lilac Micro-Octahedra) ---
    const sentinelGroup = new THREE.Group();
    kineticMasterGroup.add(sentinelGroup);

    const sentinelGeo = new THREE.OctahedronGeometry(0.12, 0);
    const sentinelMat = new THREE.MeshStandardMaterial({
      color: 0xF5F3FF,
      metalness: 0.95,
      roughness: 0.1,
      emissive: 0x635BFF,
    });
    disposables.push(sentinelGeo, sentinelMat);

    interface SentinelData {
      mesh: THREE.Mesh;
      radius: number;
      speed: number;
      angle: number;
      elevation: number;
      tiltAxis: THREE.Vector3;
    }
    const sentinels: SentinelData[] = [];
    const sentinelConfigs = [
      { radius: 2.35, speed: 0.85, elevation: 0.35, tilt: [0.3, 0.1, 0.2] },
      { radius: 2.65, speed: -0.7, elevation: -0.38, tilt: [-0.2, 0.4, -0.1] },
      { radius: 2.2, speed: 1.0, elevation: -0.12, tilt: [0.1, -0.3, 0.4] },
      { radius: 2.75, speed: -0.55, elevation: 0.45, tilt: [0.4, 0.2, -0.3] },
    ];

    sentinelConfigs.forEach((cfg) => {
      const sMesh = new THREE.Mesh(sentinelGeo, sentinelMat);
      sentinelGroup.add(sMesh);
      sentinels.push({
        mesh: sMesh,
        radius: cfg.radius,
        speed: cfg.speed,
        angle: Math.random() * Math.PI * 2,
        elevation: cfg.elevation,
        tiltAxis: new THREE.Vector3(...cfg.tilt).normalize(),
      });
    });

    // --- Part 8: Faint Orbital Trajectory Guide Lines ---
    const lineMat = new THREE.LineBasicMaterial({
      color: 0x8B7DFF,
      transparent: true,
      opacity: 0.28,
    });
    disposables.push(lineMat);
    [2.25, 2.55].forEach((r) => {
      const segments = 64;
      const points: THREE.Vector3[] = [];
      for (let i = 0; i <= segments; i++) {
        const theta = (i / segments) * Math.PI * 2;
        points.push(new THREE.Vector3(Math.cos(theta) * r, 0, Math.sin(theta) * r));
      }
      const orbitGeo = new THREE.BufferGeometry().setFromPoints(points);
      disposables.push(orbitGeo);
      const orbitLine = new THREE.Line(orbitGeo, lineMat);
      orbitLine.rotation.x = Math.PI / 6;
      kineticMasterGroup.add(orbitLine);
    });

    // --- Part 9: Ambient Data Sparkles (Cryptographic Lavender Dust) ---
    const particleCount = 36;
    const particleGeo = new THREE.BufferGeometry();
    const particlePositions = new Float32Array(particleCount * 3);
    for (let i = 0; i < particleCount; i++) {
      const u = Math.random();
      const v = Math.random();
      const theta = u * 2.0 * Math.PI;
      const phi = Math.acos(2.0 * v - 1.0);
      const dist = 1.5 + Math.random() * 1.4;
      particlePositions[i * 3] = dist * Math.sin(phi) * Math.cos(theta);
      particlePositions[i * 3 + 1] = dist * Math.sin(phi) * Math.sin(theta);
      particlePositions[i * 3 + 2] = dist * Math.cos(phi);
    }
    particleGeo.setAttribute('position', new THREE.BufferAttribute(particlePositions, 3));
    const particleMat = new THREE.PointsMaterial({
      color: 0x7B68EE,
      size: 0.045,
      transparent: true,
      opacity: 0.45,
    });
    disposables.push(particleGeo, particleMat);
    const particlePoints = new THREE.Points(particleGeo, particleMat);
    kineticMasterGroup.add(particlePoints);

    // --- Part 10: Ground Contact Shadow (Soft procedural radial wash) ---
    const shadowCanvas = document.createElement('canvas');
    shadowCanvas.width = 128;
    shadowCanvas.height = 128;
    const sCtx = shadowCanvas.getContext('2d');
    if (sCtx) {
      const grad = sCtx.createRadialGradient(64, 64, 10, 64, 64, 60);
      grad.addColorStop(0, 'rgba(91, 66, 243, 0.12)');
      grad.addColorStop(0.45, 'rgba(91, 66, 243, 0.04)');
      grad.addColorStop(1, 'rgba(91, 66, 243, 0)');
      sCtx.fillStyle = grad;
      sCtx.fillRect(0, 0, 128, 128);
    }
    const shadowTex = new THREE.CanvasTexture(shadowCanvas);
    shadowTex.colorSpace = THREE.SRGBColorSpace;
    const shadowMat = new THREE.MeshBasicMaterial({
      map: shadowTex,
      transparent: true,
      depthWrite: false,
      opacity: 0.55,
    });
    disposables.push(shadowTex, shadowMat);
    const shadowPlane = new THREE.Mesh(new THREE.PlaneGeometry(3.6, 3.6), shadowMat);
    shadowPlane.rotation.x = -Math.PI / 2;
    shadowPlane.position.y = -2.1;
    scene.add(shadowPlane);

    // Interaction State: Global window-wide cursor tracking + Dragging inertia
    const interactionState = {
      mouseX: 0,
      mouseY: 0,
      targetMouseX: 0,
      targetMouseY: 0,
      isDragging: false,
      prevPointerX: 0,
      prevPointerY: 0,
      dragRotX: 0,
      dragRotY: 0,
      targetDragRotX: 0,
      targetDragRotY: 0,
    };

    // Random Spin State: Autonomous multi-axis wandering spin
    const spinState = {
      velX: 0.3,
      velY: 0.75,
      velZ: 0.25,
      targetVelX: 0.4,
      targetVelY: 0.85,
      targetVelZ: 0.3,
      accumX: 0,
      accumY: 0,
      accumZ: 0,
      nextShift: performance.now() + 2000,
    };

    // Global cursor tracking anywhere on window so the 3D figure follows the user's cursor across the page
    const handleGlobalMouseMove = (e: MouseEvent) => {
      const nx = (e.clientX / window.innerWidth) * 2 - 1;
      const ny = -((e.clientY / window.innerHeight) * 2 - 1);
      interactionState.targetMouseX = Math.max(-1, Math.min(1, nx));
      interactionState.targetMouseY = Math.max(-1, Math.min(1, ny));
    };

    const handlePointerDown = (e: MouseEvent) => {
      interactionState.isDragging = true;
      interactionState.prevPointerX = e.clientX;
      interactionState.prevPointerY = e.clientY;
    };

    const handlePointerMove = (e: MouseEvent) => {
      if (interactionState.isDragging) {
        const deltaX = e.clientX - interactionState.prevPointerX;
        const deltaY = e.clientY - interactionState.prevPointerY;
        interactionState.prevPointerX = e.clientX;
        interactionState.prevPointerY = e.clientY;
        interactionState.targetDragRotY += deltaX * 0.008;
        interactionState.targetDragRotX += deltaY * 0.008;
      }
    };

    const handlePointerUp = () => {
      interactionState.isDragging = false;
    };

    window.addEventListener('mousemove', handleGlobalMouseMove);
    container.addEventListener('mousedown', handlePointerDown);
    window.addEventListener('mousemove', handlePointerMove);
    window.addEventListener('mouseup', handlePointerUp);

    const handleResize = () => {
      if (!container) return;
      const newW = container.clientWidth;
      const newH = container.clientHeight;
      camera.aspect = newW / newH;
      camera.updateProjectionMatrix();
      renderer.setSize(newW, newH);
    };
    window.addEventListener('resize', handleResize);

    // 5. Render Loop with Exponential Kinematics Damping & Software WebGL Guard
    let frameId = 0;
    let lastTime = performance.now();

    function afterDraw(cb: () => void) {
      const gl = renderer.getContext() as WebGL2RenderingContext;
      const sync = gl.fenceSync ? gl.fenceSync(gl.SYNC_GPU_COMMANDS_COMPLETE, 0) : null;
      gl.flush();
      if (!sync) {
        frameId = window.setTimeout(cb, 80);
        return;
      }
      const poll = () => {
        const state = gl.getSyncParameter(sync, gl.SYNC_STATUS);
        if (state === gl.UNSIGNALED) {
          frameId = window.setTimeout(poll, 5);
        } else {
          gl.deleteSync(sync);
          frameId = window.setTimeout(cb, 80);
        }
      };
      frameId = window.setTimeout(poll, 5);
    }

    const animate = () => {
      const now = performance.now();
      const delta = Math.min((now - lastTime) / 1000, 0.1);
      lastTime = now;
      const time = now / 1000;

      // 1. Random Spin Momentum Generation (changes randomly every 2-3.5 seconds)
      if (now > spinState.nextShift) {
        spinState.nextShift = now + 1800 + Math.random() * 2000;
        // Random rotational velocities with varied speeds and direction flips
        const dirY = Math.random() > 0.35 ? 1 : -1;
        spinState.targetVelY = dirY * (0.5 + Math.random() * 0.8);
        spinState.targetVelX = (Math.random() - 0.5) * 0.85;
        spinState.targetVelZ = (Math.random() - 0.5) * 0.7;
      }

      // Smoothly interpolate current rotational velocities towards target
      spinState.velX = THREE.MathUtils.damp(spinState.velX, spinState.targetVelX, 1.8, delta);
      spinState.velY = THREE.MathUtils.damp(spinState.velY, spinState.targetVelY, 1.8, delta);
      spinState.velZ = THREE.MathUtils.damp(spinState.velZ, spinState.targetVelZ, 1.8, delta);

      // Accumulate multi-axis random spinning angles
      spinState.accumX += spinState.velX * delta;
      spinState.accumY += spinState.velY * delta;
      spinState.accumZ += spinState.velZ * delta;

      // 2. Smooth Cursor Following (tilts dynamically towards pointer anywhere on screen)
      interactionState.mouseX = THREE.MathUtils.damp(
        interactionState.mouseX,
        interactionState.targetMouseX,
        3.5,
        delta
      );
      interactionState.mouseY = THREE.MathUtils.damp(
        interactionState.mouseY,
        interactionState.targetMouseY,
        3.5,
        delta
      );
      interactionState.dragRotX = THREE.MathUtils.damp(
        interactionState.dragRotX,
        interactionState.targetDragRotX,
        6.0,
        delta
      );
      interactionState.dragRotY = THREE.MathUtils.damp(
        interactionState.dragRotY,
        interactionState.targetDragRotY,
        6.0,
        delta
      );

      // 3. Weightless floating oscillation
      const floatY = Math.sin(time * 1.3) * 0.08;
      kineticMasterGroup.position.y = floatY;

      // Dynamic shadow scale & opacity mirroring floating altitude
      const shadowScale = 1.0 - floatY * 0.35;
      shadowPlane.scale.set(shadowScale, shadowScale, 1);
      shadowMat.opacity = 0.52 - floatY * 0.25;

      // 4. Random Gyroscopic Component Rotations
      outerRingMesh.rotation.z += (0.35 + spinState.velZ * 0.5) * delta;
      outerRingMesh.rotation.x = Math.sin(time * 0.7 + spinState.accumX) * 0.22;

      midRingMesh.rotation.y += (0.55 + spinState.velY * 0.5) * delta;
      midRingMesh.rotation.x += (0.42 + spinState.velX * 0.5) * delta;

      innerRingMesh.rotation.z -= (0.65 + spinState.velZ * 0.6) * delta;
      innerRingMesh.rotation.y = Math.cos(time * 0.9 + spinState.accumY) * 0.28;

      // Faceted obsidian core & glowing inner heart
      coreMesh.rotation.y -= (0.5 + spinState.velY * 0.6) * delta;
      coreMesh.rotation.x -= (0.35 + spinState.velX * 0.4) * delta;
      coreMesh.rotation.z += (0.2 + spinState.velZ * 0.4) * delta;

      heartMesh.rotation.x += 0.8 * delta;
      heartMesh.rotation.y += 0.9 * delta;
      const pulse = 1.0 + Math.sin(time * 3.2) * 0.08;
      heartMesh.scale.set(pulse, pulse, pulse);
      heartMat.emissiveIntensity = 1.6 + Math.sin(time * 3.2) * 0.4;
      corePointLight.intensity = 2.2 + Math.sin(time * 3.2) * 0.5;

      // Orbiting Sentinels
      sentinels.forEach((s) => {
        s.angle += (s.speed + spinState.velY * 0.3) * delta;
        const currentR = s.radius + Math.sin(time * 2.0 + s.angle) * 0.08;
        s.mesh.position.set(
          Math.cos(s.angle) * currentR,
          s.elevation + Math.sin(time * 2.5 + s.angle) * 0.12,
          Math.sin(s.angle) * currentR
        );
        s.mesh.rotation.x += 2.0 * delta;
        s.mesh.rotation.y += 2.2 * delta;
      });

      // Drifting ambient particle dust
      particlePoints.rotation.y += 0.12 * delta;
      particlePoints.rotation.x = Math.sin(time * 0.5) * 0.06;

      // 5. Master group orientation: Combines random multi-axis spin with cursor-following tilt
      const cursorTiltX = -interactionState.mouseY * 0.65;
      const cursorTiltY = interactionState.mouseX * 0.75;

      _targetEuler.set(
        cursorTiltX + spinState.accumX * 0.6 + interactionState.dragRotX,
        cursorTiltY + spinState.accumY * 0.8 + interactionState.dragRotY,
        spinState.accumZ * 0.5
      );
      kineticMasterGroup.rotation.x = THREE.MathUtils.damp(
        kineticMasterGroup.rotation.x,
        _targetEuler.x,
        3.5,
        delta
      );
      kineticMasterGroup.rotation.y = THREE.MathUtils.damp(
        kineticMasterGroup.rotation.y,
        _targetEuler.y,
        3.5,
        delta
      );
      kineticMasterGroup.rotation.z = THREE.MathUtils.damp(
        kineticMasterGroup.rotation.z,
        _targetEuler.z,
        3.5,
        delta
      );

      renderer.render(scene, camera);

      if (isSoftwareGl) {
        afterDraw(animate);
      } else {
        frameId = requestAnimationFrame(animate);
      }
    };

    if (!isSoftwareGl) {
      renderer.compile(scene, camera);
    }
    animate();

    return () => {
      cancelAnimationFrame(frameId);
      clearTimeout(frameId);
      window.removeEventListener('resize', handleResize);
      window.removeEventListener('mousemove', handleGlobalMouseMove);
      window.removeEventListener('mousemove', handlePointerMove);
      window.removeEventListener('mouseup', handlePointerUp);
      container.removeEventListener('mousedown', handlePointerDown);

      disposables.forEach((res) => res.dispose());
      renderer.dispose();
      if (container.contains(renderer.domElement)) {
        container.removeChild(renderer.domElement);
      }
    };
  }, []);

  return (
    <div
      ref={mountRef}
      data-testid="threejs-hero-canvas"
      className="relative w-full h-[440px] sm:h-[500px] lg:h-[560px] flex items-center justify-center cursor-grab active:cursor-grabbing select-none"
      title="Suspended 3D Cryptographic Defense Core — follows cursor & spins randomly"
    />
  );
};
