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

(major, minor, _) = cv2.__version__.split(".")

if (major<3 and minor<7) or minor<3:
    model = cv2.createLBPHFaceRecognizer()
else:
    cv2.face
    model = cv2.face.createLBPHFaceRecognizer()

model.load(LBPH_MODEL_PATH)

testImage = cv2.imread(IMAGE_PATH, 0)

if testImage is None:
    print >> sys.stderr, "Error: Could not read '{}'".format(IMAGE_PATH)
    sys.exit(1)

predictedData = model.predict(testImage)

print json.dumps(predictedData)
