import os, sys, cv2, json

if 3 > len(sys.argv):
    print >> sys.stderr, 'Try running: faceRecognizer.py <path/to/lbph_model.xml> <path/to/image.ext>'
    sys.exit(1)

# Init
LBPH_MODEL_PATH = sys.argv[1]
IMAGE_PATH      = sys.argv[2]

if not os.path.isfile(LBPH_MODEL_PATH):
    print >> sys.stderr, "No model state file '{}'".format(LBPH_MODEL_PATH)
    sys.exit(1)

if not os.path.isfile(IMAGE_PATH):
    print >> sys.stderr, "No test data file at '{}'".format(IMAGE_PATH)
    sys.exit(1)

model = cv2.createLBPHFaceRecognizer()
model.load(LBPH_MODEL_PATH)

testImage = cv2.imread(IMAGE_PATH, 0)

if testImage is None:
    print >> sys.stderr, "Error: Could not read '{}'".format(IMAGE_PATH)
    sys.exit(1)

predictedData = model.predict(testImage)

print json.dumps(predictedData)