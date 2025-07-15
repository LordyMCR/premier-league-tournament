<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import PrimaryButton from './PrimaryButton.vue'
import SecondaryButton from './SecondaryButton.vue'

const props = defineProps({
    imageFile: File,
    show: Boolean,
})

const emit = defineEmits(['close', 'crop'])

const canvas = ref(null)
const cropCanvas = ref(null)
const smallPreview = ref(null)
const ctx = ref(null)
const cropCtx = ref(null)
const smallCtx = ref(null)
const image = ref(null)
const isDragging = ref(false)
const isResizing = ref(false)

// Crop area properties
const cropArea = ref({
    x: 100,
    y: 100,
    size: 200,
    minSize: 50,
    maxSize: 400
})

// Mouse/touch tracking
const lastMouse = ref({ x: 0, y: 0 })
const isMobile = ref(false)

// Watch for changes in show prop
watch(() => props.show, async (newValue) => {
    if (newValue && props.imageFile) {
        await nextTick()
        initializeCropper()
    }
})

onMounted(() => {
    // Detect mobile device
    isMobile.value = 'ontouchstart' in window || navigator.maxTouchPoints > 0
})

const initializeCropper = async () => {
    if (!canvas.value || !cropCanvas.value || !smallPreview.value) return

    const img = new Image()
    img.onload = () => {
        image.value = img
        
        // Set up main canvas
        const canvasEl = canvas.value
        const cropCanvasEl = cropCanvas.value
        const smallCanvasEl = smallPreview.value
        
        // Calculate canvas size to fit image while maintaining aspect ratio
        // Make smaller on mobile
        const maxWidth = isMobile.value ? 350 : 600
        const maxHeight = isMobile.value ? 250 : 400
        
        let canvasWidth = img.width
        let canvasHeight = img.height
        
        if (canvasWidth > maxWidth) {
            canvasHeight = (canvasHeight * maxWidth) / canvasWidth
            canvasWidth = maxWidth
        }
        
        if (canvasHeight > maxHeight) {
            canvasWidth = (canvasWidth * maxHeight) / canvasHeight
            canvasHeight = maxHeight
        }
        
        canvasEl.width = canvasWidth
        canvasEl.height = canvasHeight
        cropCanvasEl.width = isMobile.value ? 200 : 300
        cropCanvasEl.height = isMobile.value ? 200 : 300
        smallCanvasEl.width = 64
        smallCanvasEl.height = 64
        
        ctx.value = canvasEl.getContext('2d')
        cropCtx.value = cropCanvasEl.getContext('2d')
        smallCtx.value = smallCanvasEl.getContext('2d')
        
        // Initialize crop area in center
        const initialSize = Math.min(150, canvasWidth - 100, canvasHeight - 100)
        cropArea.value = {
            x: Math.max(25, (canvasWidth - initialSize) / 2),
            y: Math.max(25, (canvasHeight - initialSize) / 2),
            size: initialSize,
            minSize: 50,
            maxSize: Math.min(canvasWidth - 20, canvasHeight - 20)
        }
        
        draw()
    }
    
    img.src = URL.createObjectURL(props.imageFile)
}

const draw = () => {
    if (!ctx.value || !image.value) return
    
    const canvasEl = canvas.value
    
    // Clear canvas
    ctx.value.clearRect(0, 0, canvasEl.width, canvasEl.height)
    
    // Draw image
    ctx.value.drawImage(image.value, 0, 0, canvasEl.width, canvasEl.height)
    
    // Draw crop overlay
    drawCropOverlay()
    
    // Update preview
    updatePreview()
}

const drawCropOverlay = () => {
    const canvasEl = canvas.value
    const crop = cropArea.value
    
    // Draw dark overlay over entire image
    ctx.value.fillStyle = 'rgba(0, 0, 0, 0.5)'
    ctx.value.fillRect(0, 0, canvasEl.width, canvasEl.height)
    
    // Clear the crop area (circular)
    ctx.value.globalCompositeOperation = 'destination-out'
    ctx.value.beginPath()
    ctx.value.arc(crop.x + crop.size / 2, crop.y + crop.size / 2, crop.size / 2, 0, 2 * Math.PI)
    ctx.value.fill()
    
    // Reset composite operation
    ctx.value.globalCompositeOperation = 'source-over'
    
    // Draw crop circle border
    ctx.value.strokeStyle = '#10B981'
    ctx.value.lineWidth = isMobile.value ? 2 : 3
    ctx.value.beginPath()
    ctx.value.arc(crop.x + crop.size / 2, crop.y + crop.size / 2, crop.size / 2, 0, 2 * Math.PI)
    ctx.value.stroke()
    
    // Draw resize handles (larger on mobile)
    const handleSize = isMobile.value ? 20 : 12
    const handles = [
        { x: crop.x + crop.size - handleSize/2, y: crop.y + crop.size / 2 }, // Right
        { x: crop.x + crop.size / 2, y: crop.y + crop.size - handleSize/2 }, // Bottom
        { x: crop.x + crop.size - handleSize/2, y: crop.y + crop.size - handleSize/2 }, // Bottom-right
    ]
    
    ctx.value.fillStyle = '#10B981'
    ctx.value.strokeStyle = '#ffffff'
    ctx.value.lineWidth = 2
    
    handles.forEach(handle => {
        ctx.value.fillRect(handle.x - handleSize/2, handle.y - handleSize/2, handleSize, handleSize)
        ctx.value.strokeRect(handle.x - handleSize/2, handle.y - handleSize/2, handleSize, handleSize)
    })
}

const updatePreview = () => {
    if (!cropCtx.value || !smallCtx.value || !image.value) return
    
    const crop = cropArea.value
    const canvasEl = canvas.value
    
    // Calculate source coordinates on original image
    const scaleX = image.value.width / canvasEl.width
    const scaleY = image.value.height / canvasEl.height
    
    const sourceX = crop.x * scaleX
    const sourceY = crop.y * scaleY
    const sourceSize = crop.size * Math.max(scaleX, scaleY)
    
    // Update large preview
    const previewSize = isMobile.value ? 200 : 300
    updateCanvasPreview(cropCtx.value, previewSize, sourceX, sourceY, sourceSize)
    
    // Update small preview
    updateCanvasPreview(smallCtx.value, 64, sourceX, sourceY, sourceSize)
}

const updateCanvasPreview = (context, size, sourceX, sourceY, sourceSize) => {
    // Clear canvas
    context.clearRect(0, 0, size, size)
    
    // Create circular clipping path
    context.save()
    context.beginPath()
    context.arc(size / 2, size / 2, size / 2, 0, 2 * Math.PI)
    context.clip()
    
    // Draw cropped image
    context.drawImage(
        image.value,
        sourceX, sourceY, sourceSize, sourceSize,
        0, 0, size, size
    )
    
    context.restore()
}

// Universal event position getter (works for both mouse and touch)
const getEventPos = (e) => {
    const rect = canvas.value.getBoundingClientRect()
    const clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0)
    const clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0)
    
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    }
}

const isInCropArea = (eventPos) => {
    const crop = cropArea.value
    const centerX = crop.x + crop.size / 2
    const centerY = crop.y + crop.size / 2
    const distance = Math.sqrt(Math.pow(eventPos.x - centerX, 2) + Math.pow(eventPos.y - centerY, 2))
    return distance <= crop.size / 2
}

const isOnResizeHandle = (eventPos) => {
    const crop = cropArea.value
    const handleSize = isMobile.value ? 20 : 12
    const handles = [
        { x: crop.x + crop.size - handleSize/2, y: crop.y + crop.size / 2 },
        { x: crop.x + crop.size / 2, y: crop.y + crop.size - handleSize/2 },
        { x: crop.x + crop.size - handleSize/2, y: crop.y + crop.size - handleSize/2 },
    ]
    
    return handles.some(handle => 
        eventPos.x >= handle.x - handleSize/2 && 
        eventPos.x <= handle.x + handleSize/2 &&
        eventPos.y >= handle.y - handleSize/2 && 
        eventPos.y <= handle.y + handleSize/2
    )
}

// Mouse events
const onMouseDown = (e) => {
    e.preventDefault()
    const eventPos = getEventPos(e)
    lastMouse.value = eventPos
    
    if (isOnResizeHandle(eventPos)) {
        isResizing.value = true
        canvas.value.style.cursor = 'nw-resize'
    } else if (isInCropArea(eventPos)) {
        isDragging.value = true
        canvas.value.style.cursor = 'move'
    }
}

const onMouseMove = (e) => {
    e.preventDefault()
    const eventPos = getEventPos(e)
    
    if (isResizing.value) {
        handleResize(eventPos)
    } else if (isDragging.value) {
        handleDrag(eventPos)
    } else if (!isMobile.value) {
        // Update cursor based on hover (desktop only)
        updateCursor(eventPos)
    }
}

const onMouseUp = (e) => {
    e.preventDefault()
    isDragging.value = false
    isResizing.value = false
    canvas.value.style.cursor = 'default'
}

// Touch events
const onTouchStart = (e) => {
    e.preventDefault()
    if (e.touches.length === 1) {
        onMouseDown(e)
    }
}

const onTouchMove = (e) => {
    e.preventDefault()
    if (e.touches.length === 1) {
        onMouseMove(e)
    }
}

const onTouchEnd = (e) => {
    e.preventDefault()
    onMouseUp(e)
}

const handleResize = (eventPos) => {
    // Resize based on distance from center
    const crop = cropArea.value
    const centerX = crop.x + crop.size / 2
    const centerY = crop.y + crop.size / 2
    const newRadius = Math.max(
        crop.minSize / 2,
        Math.min(
            crop.maxSize / 2,
            Math.sqrt(Math.pow(eventPos.x - centerX, 2) + Math.pow(eventPos.y - centerY, 2))
        )
    )
    
    const newSize = newRadius * 2
    const canvasEl = canvas.value
    
    // Ensure crop area stays within canvas bounds
    const minX = 0
    const minY = 0
    const maxX = canvasEl.width - newSize
    const maxY = canvasEl.height - newSize
    
    crop.x = Math.max(minX, Math.min(maxX, centerX - newRadius))
    crop.y = Math.max(minY, Math.min(maxY, centerY - newRadius))
    crop.size = newSize
    
    draw()
}

const handleDrag = (eventPos) => {
    // Move crop area
    const deltaX = eventPos.x - lastMouse.value.x
    const deltaY = eventPos.y - lastMouse.value.y
    
    const crop = cropArea.value
    const canvasEl = canvas.value
    
    crop.x = Math.max(0, Math.min(canvasEl.width - crop.size, crop.x + deltaX))
    crop.y = Math.max(0, Math.min(canvasEl.height - crop.size, crop.y + deltaY))
    
    draw()
    lastMouse.value = eventPos
}

const updateCursor = (eventPos) => {
    if (isOnResizeHandle(eventPos)) {
        canvas.value.style.cursor = 'nw-resize'
    } else if (isInCropArea(eventPos)) {
        canvas.value.style.cursor = 'move'
    } else {
        canvas.value.style.cursor = 'default'
    }
}

const cropImage = () => {
    if (!image.value) return
    
    const crop = cropArea.value
    const canvasEl = canvas.value
    
    // Calculate source coordinates on original image
    const scaleX = image.value.width / canvasEl.width
    const scaleY = image.value.height / canvasEl.height
    
    const sourceX = crop.x * scaleX
    const sourceY = crop.y * scaleY
    const sourceSize = crop.size * Math.max(scaleX, scaleY)
    
    // Create final crop canvas
    const finalCanvas = document.createElement('canvas')
    finalCanvas.width = 300
    finalCanvas.height = 300
    const finalCtx = finalCanvas.getContext('2d')
    
    // Create circular clipping path
    finalCtx.save()
    finalCtx.beginPath()
    finalCtx.arc(150, 150, 150, 0, 2 * Math.PI)
    finalCtx.clip()
    
    // Draw cropped image
    finalCtx.drawImage(
        image.value,
        sourceX, sourceY, sourceSize, sourceSize,
        0, 0, 300, 300
    )
    
    finalCtx.restore()
    
    // Convert to blob and emit
    finalCanvas.toBlob((blob) => {
        emit('crop', blob)
    }, 'image/png', 0.9)
}

const cancel = () => {
    emit('close')
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-3 sm:p-6 max-w-4xl w-full mx-2 sm:mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <h2 class="text-lg sm:text-xl font-bold text-white">Crop Profile Picture</h2>
                <button @click="cancel" class="text-white/70 hover:text-white p-2">
                    <i class="fas fa-times text-lg sm:text-xl"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Main Cropping Area -->
                <div class="lg:col-span-2">
                    <h3 class="text-base sm:text-lg font-medium text-white mb-2 sm:mb-3">Adjust Your Photo</h3>
                    <p class="text-white/70 text-xs sm:text-sm mb-3 sm:mb-4">
                        <span v-if="isMobile">Tap and drag to move • Drag corners to resize</span>
                        <span v-else>Drag to move • Drag corner handles to resize</span>
                        • Only the area inside the circle will be saved
                    </p>
                    
                    <div class="bg-gray-900/50 rounded-lg p-2 sm:p-4 overflow-auto">
                        <canvas 
                            ref="canvas"
                            @mousedown="onMouseDown"
                            @mousemove="onMouseMove"
                            @mouseup="onMouseUp"
                            @mouseleave="onMouseUp"
                            @touchstart="onTouchStart"
                            @touchmove="onTouchMove"
                            @touchend="onTouchEnd"
                            class="border border-gray-600/50 rounded cursor-default max-w-full"
                        ></canvas>
                    </div>
                </div>
                
                <!-- Preview Area -->
                <div class="lg:col-span-1">
                    <h3 class="text-base sm:text-lg font-medium text-white mb-2 sm:mb-3">Preview</h3>
                    <p class="text-white/70 text-xs sm:text-sm mb-3 sm:mb-4">
                        This is how your profile picture will appear
                    </p>
                    
                    <div class="flex flex-col items-center space-y-3 sm:space-y-4">
                        <!-- Large Preview -->
                        <div class="relative">
                            <canvas 
                                ref="cropCanvas"
                                class="rounded-full border-4 border-emerald-400/50 shadow-2xl"
                            ></canvas>
                        </div>
                        
                        <!-- Small Preview -->
                        <div class="text-center">
                            <p class="text-white/70 text-xs mb-2">Small size preview:</p>
                            <canvas 
                                ref="smallPreview"
                                width="64" 
                                height="64"
                                class="rounded-full border-2 border-white/20"
                            ></canvas>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col space-y-2 sm:space-y-3 mt-4 sm:mt-6">
                        <PrimaryButton @click="cropImage" class="w-full text-sm sm:text-base">
                            <i class="fas fa-crop mr-2"></i>
                            Save Cropped Image
                        </PrimaryButton>
                        
                        <SecondaryButton @click="cancel" class="w-full text-sm sm:text-base">
                            <i class="fas fa-times mr-2"></i>
                            Cancel
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-blue-500/10 border border-blue-400/30 rounded-lg">
                <h4 class="text-blue-300 font-medium mb-2 text-sm sm:text-base">
                    <i class="fas fa-info-circle mr-2"></i>
                    How to use:
                </h4>
                <ul class="text-blue-200/90 text-xs sm:text-sm space-y-1">
                    <li v-if="isMobile">• Tap and drag inside the circle to move the crop area</li>
                    <li v-else>• Click and drag inside the circle to move the crop area</li>
                    <li>• Drag the corner handles to resize the crop area</li>
                    <li>• The preview shows exactly how your avatar will look</li>
                    <li>• {{ isMobile ? 'Tap' : 'Click' }} "Save Cropped Image" when you're happy with the result</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
canvas {
    touch-action: none;
    user-select: none;
}
</style> 