#!/bin/bash
set -e

IMAGE_NAME="rix4uni/krazeplanet:main"

echo "======================================================"
echo "  Building Docker image: $IMAGE_NAME"
echo "======================================================"

# Build local image
docker build -t "$IMAGE_NAME" .

echo "======================================================"
echo "  Build successful! To push to Docker Hub, run:"
echo "    docker push $IMAGE_NAME"
echo "======================================================"
